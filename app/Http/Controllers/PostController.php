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
        $validated = $request->validate([
            'shop_name' => 'required|string|max:255', // 文字数制限追加
            'score' => 'required|numeric|min:0|max:100',
            'comment' => 'nullable|string|max:1000', // 文字数制限追加
            'image' => 'required|image|max:10240',
            'address' => 'nullable|string|max:255',
            'google_place_id' => 'nullable|string|max:255',
        ]);

        // トランザクション開始（失敗したら全部ロールバック）
        // post変数を外で使うために return で受け取る
        $post = DB::transaction(function () use ($request, $validated) {
            $user = Auth::user();

            // 1. 店舗の特定・作成ロジック
            $shop = null;
            if ($request->google_place_id) {
                $shop = Shop::where('google_place_id', $request->google_place_id)->first();
            }
            if (!$shop) {
                $shop = Shop::where('name', $validated['shop_name'])->first();
            }
            if (!$shop) {
                $shop = Shop::create([
                    'name' => $validated['shop_name'],
                    'google_place_id' => $request->google_place_id,
                    'address' => $request->address,
                ]);
            } else {
                if (empty($shop->google_place_id) && $request->google_place_id) {
                    $shop->update([
                        'google_place_id' => $request->google_place_id,
                        'address' => $request->address ?? $shop->address,
                    ]);
                }
            }

            // 2. 画像保存処理
            // ※厳密には画像保存はDBトランザクションで戻せませんが、
            //   失敗時に例外を投げればDB側のゴミデータは防げます。
            $imagePath = null;
            if ($request->hasFile('image')) {
                $manager = new ImageManager(new Driver());
                $image = $manager->read($request->file('image'));
                $image->scale(width: 800);
                $encoded = $image->toWebp(quality: 75);
                
                $fileName = 'uploads/posts/' . Str::random(40) . '.webp';
                $dirPath = public_path('uploads/posts');
                
                if (!file_exists($dirPath)) {
                    mkdir($dirPath, 0755, true); // 0777は危険なので0755推奨
                }
                
                file_put_contents(public_path($fileName), $encoded);
                $imagePath = $fileName;
            }

            // 3. 投稿保存
            $post = new Post();
            $post->shop_id = $shop->id;
            $post->shop_name = $shop->name; // バリデーション値よりShopモデルの値を優先
            $post->user_id = $user->id;
            $post->score = $validated['score'];
            $post->comment = $validated['comment'];
            $post->eaten_at = now();
            $post->image_path = $imagePath;
            $post->save(); // ここでID確定

            // 4. ポイント計算
            $points = 1; 
            $hasCampaign = Campaign::where('shop_id', $shop->id)
                ->where('is_active', true)
                ->exists();

            if ($hasCampaign) {
                $points = 2; 
            }

            $post->earned_points = $points; 
            $post->save(); 

            // 5. ユーザースコア更新（incrementは便利ですが、モデル更新と競合しないよう注意）
            $user->increment('total_score', $points);
            $user->increment('posts_count');

            // 6. 店舗スコア更新（さっき追加したやつ）
            $shop->updateRankingData();

            // 7. ラリー制覇判定
            $relatedRallies = $user->joinedRallies()
                ->whereHas('shops', function($q) use ($shop) {
                    $q->where('shops.id', $shop->id);
                })
                ->get();

            foreach ($relatedRallies as $rally) {
                $rally->checkAndComplete($user);
            }

            return $post; // トランザクションの結果としてpostを返す
        });

        // 8. 通知処理（トランザクションの外で行うのが定石）
        // ※本当はここを「キュー（Queue）」に入れるのが正解ですが、今はこれでOK
        // ※ユーザー数が増えたら絶対に修正が必要な箇所です
        $users = User::where('id', '!=', Auth::id())->get();
        if ($users->isNotEmpty()) {
            Notification::send($users, new NewPost($post));
        }

        return redirect()->route('profile.index')->with('success', '投稿しました！');
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
            $shop = $post->shop;
            
            // 投稿を削除
            $post->delete();

            // お店のランキングデータを再計算（削除された分を反映）
            if ($shop) {
                $shop->updateRankingData();
            }
            
            
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

        if ($post->shop) {
            $post->shop->updateRankingData();
        }

        return redirect()->route('profile.index')->with('success', '投稿を更新しました！');
    }
}