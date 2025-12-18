<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Shop;
use App\Models\Campaign;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Notifications\NewPost; 
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use App\Models\Rally;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    // ① 投稿フォームを表示する
    public function create()
    {
        return view('posts.create');
    }

    // ② 投稿を保存する
    public function store(Request $request)
    {
        // 入力チェック（画像も追加）
        $validated = $request->validate([
            'shop_name' => 'required',
            'score' => 'required|numeric|min:0|max:100',
            'comment' => 'nullable|string',
            'image' => 'required|image|max:10240',
            // ▼▼▼ 追加: 住所などのバリデーション（nullableでOK） ▼▼▼
            'address' => 'nullable|string',
            'google_place_id' => 'nullable|string',
        ]);

        // ▼▼▼ 修正: お店を探す・作るロジック（DailyRamenと同じ賢いロジックに統一） ▼▼▼
        $shop = null;

        // ① Google Place ID があれば、それで探す
        if ($request->google_place_id) {
            $shop = Shop::where('google_place_id', $request->google_place_id)->first();
        }

        // ② なければ、店名で探してみる
        if (!$shop) {
            $shop = Shop::where('name', $validated['shop_name'])->first();
        }

        // ③ それでもなければ、新規作成する
        if (!$shop) {
            $shop = Shop::create([
                // ▼▼▼ 修正: $name -> $validated['shop_name'] ▼▼▼
                'name' => $validated['shop_name'],
                // ▼▼▼ 修正: $placeId -> $request->google_place_id ▼▼▼
                'google_place_id' => $request->google_place_id,
                // ▼▼▼ 修正: $address -> $request->address ▼▼▼
                'address' => $request->address,
            ]);
        } else {
            // D. 既存の店なら、足りない情報を補完してあげる（親切設計）
            // ▼▼▼ 修正: $placeId -> $request->google_place_id ▼▼▼
            if (empty($shop->google_place_id) && $request->google_place_id) {
                $shop->update([
                    'google_place_id' => $request->google_place_id,
                    'address' => $request->address ?? $shop->address,
                ]);
            }
        }
        // ▲▲▲ 修正ここまで ▲▲▲

        // 2. 投稿を保存
        $post = new \App\Models\Post();
        $post->shop_id = $shop->id;
        $post->shop_name = $validated['shop_name']; // ★追加: Postsテーブルにも店名を保存
        $post->user_id = \Illuminate\Support\Facades\Auth::id(); 
        $post->score = $validated['score'];
        $post->comment = $validated['comment'];
        $post->eaten_at = now(); // またはフォームから受け取るなら $request->eaten_at

        // ★★★ 画像保存処理（圧縮・リサイズ版） ★★★
        if ($request->hasFile('image')) {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($request->file('image'));
            $image->scale(width: 800);
            $encoded = $image->toWebp(quality: 75);
    
            // ★修正: 保存先を 'uploads/posts/' に統一
            $fileName = 'uploads/posts/' . Str::random(40) . '.webp';
            
            // ディレクトリがない場合は作成
            if (!file_exists(public_path('uploads/posts'))) {
                mkdir(public_path('uploads/posts'), 0777, true);
            }
            
            $storagePath = public_path($fileName);
            file_put_contents($storagePath, $encoded); 
            $post->image_path = $fileName;
        }

        $post->save();

        // ★★★ ポイント計算 ★★★
        $points = 1; 

        // 投稿した店でキャンペーンやってるか確認
        $hasCampaign = Campaign::where('shop_id', $shop->id)
            ->where('is_active', true)
            ->exists();

        if ($hasCampaign) {
            $points = 2; 
        }

        $post->earned_points = $points; 
        $post->save(); 

        Auth::user()->increment('total_score', $points);
        Auth::user()->increment('posts_count');

        // ★★★ 追加: ラリー制覇判定ロジック ★★★
        // ① 「今回行った店」を含んでいる、かつ「自分が参加中」のラリーを取得
        $relatedRallies = Auth::user()->joinedRallies()
            ->whereHas('shops', function($q) use ($shop) {
                $q->where('shops.id', $shop->id);
            })
            ->get();

        // ② それぞれ判定を実行
        foreach ($relatedRallies as $rally) {
            $rally->checkAndComplete(Auth::user());
        }
        // ★★★ ここまで ★★★

        // 通知処理
        $users = User::where('id', '!=', Auth::id())->get();
        Notification::send($users, new NewPost($post));

        return redirect('/');
    }

    public function destroy(Post $post)
    {
        if (Auth::id() !== $post->user_id) {
            abort(403);
        }

        DB::transaction(function () use ($post) {
            $user = $post->user;

            // ====================================================
            // 1. 削除前の準備：影響を受けるかもしれない「達成済みラリー」を特定
            // ====================================================
            // 「この店を含んでいる」かつ「達成済み」のラリーをリストアップ
            $potentiallyAffectedRallies = $user->joinedRallies()
                ->wherePivot('is_completed', true)
                ->whereHas('shops', function($q) use ($post) {
                    $q->where('shops.id', $post->shop_id);
                })
                ->with('shops') // 店の総数を知るためロード
                ->get();

            // ====================================================
            // 2. 投稿自体の削除処理（ポイント・杯数回収）
            // ====================================================
            $user->decrement('total_score', $post->earned_points);
            $user->decrement('posts_count');
            
            // 投稿を削除（これで「最新の制覇状況」が変わる可能性がある）
            $post->delete();

            // ====================================================
            // 3. 削除後の再審査：コンプリート剥奪チェック
            // ====================================================
            foreach ($potentiallyAffectedRallies as $rally) {
                // A. このラリーの条件（期間・対象店）で、現在有効な投稿数を数え直す
                $validPostsCount = $user->posts()
                    ->whereIn('shop_id', $rally->shops->pluck('id')) // ラリー対象店
                    ->where('eaten_at', '>=', $rally->created_at)    // ラリー作成日以降
                    ->distinct('shop_id')                            // 同じ店は1回カウント
                    ->count();

                // B. ラリーの必要店舗数
                $totalShops = $rally->shops->count();

                // C. 「投稿を消したせいで、店舗数が足りなくなった」場合
                if ($validPostsCount < $totalShops) {
                    // 😱 コンプリート剥奪！
                    
                    // 中間テーブルを「未達成」に戻す
                    $user->joinedRallies()->updateExistingPivot($rally->id, [
                        'is_completed' => false,
                        'completed_at' => null,
                    ]);

                    // ユーザーのスコアからボーナス(5pt)を没収
                    $user->decrement('total_score', 5);
                    
                    // 制覇数を減らす
                    $user->decrement('completed_rallies_count');
                }
            }
        });

        return back()->with('success', '投稿を削除しました。');
    }

    // ③ 編集画面を表示する
    public function edit(Post $post)
    {
        if (Auth::id() !== $post->user_id) {
            abort(403);
        }

        return view('posts.edit', compact('post'));
    }

    // ④ 投稿を更新する
    public function update(Request $request, Post $post)
    {
        if (Auth::id() !== $post->user_id) {
            abort(403);
        }

        $validated = $request->validate([
            'shop_name' => 'required',
            'score' => 'required|numeric|min:0|max:100',
            'comment' => 'nullable|string',
            'image' => 'nullable|image|max:10240',
            // update時も住所情報があれば受け取る（必須ではない）
            'address' => 'nullable|string',
            'google_place_id' => 'nullable|string',
        ]);

        // 1. 店の更新ロジック（storeと同じく賢く検索・作成・補完）
        $shop = null;
        
        // ① Google Place ID があれば、それで探す
        if ($request->google_place_id) {
            $shop = Shop::where('google_place_id', $request->google_place_id)->first();
        }
        
        // ② なければ、店名で探してみる
        if (!$shop) {
            $shop = Shop::where('name', $validated['shop_name'])->first();
        }
        
        // ③ それでもなければ、新規作成する
        if (!$shop) {
            $shop = Shop::create([
                'name' => $validated['shop_name'],
                'address' => $request->address,
                'google_place_id' => $request->google_place_id,
            ]);
        } else {
            // ④ 既存の店なら、足りない情報を補完してあげる（親切設計）
            // ▼▼▼ ここを追加 ▼▼▼
            if (empty($shop->google_place_id) && $request->google_place_id) {
                $shop->update([
                    'google_place_id' => $request->google_place_id,
                    'address' => $request->address ?? $shop->address,
                ]);
            }
        }

        // 2. データのセット
        $post->shop_id = $shop->id;
        $post->shop_name = $validated['shop_name']; // ★店名も更新
        $post->score = $validated['score'];
        $post->comment = $validated['comment'];

        // ★★★ 画像の差し替え処理 ★★★
        if ($request->hasFile('image')) {
            // A. 古い画像があれば削除
            if ($post->image_path && file_exists(public_path($post->image_path))) {
                unlink(public_path($post->image_path));
            }

            // B. 新しい画像を保存
            $manager = new ImageManager(new Driver());
            $image = $manager->read($request->file('image'));
            $image->scale(width: 800);
            $encoded = $image->toWebp(quality: 75);

            // ★修正: 保存先を 'uploads/posts/' に統一
            $fileName = 'uploads/posts/' . Str::random(40) . '.webp';
            
            if (!file_exists(public_path('uploads/posts'))) {
                mkdir(public_path('uploads/posts'), 0777, true);
            }
            
            $storagePath = public_path($fileName);
            file_put_contents($storagePath, $encoded);

            $post->image_path = $fileName;
        }

        $post->save();

        return redirect()->route('profile.index')->with('success', '投稿を更新しました！');
    }
}