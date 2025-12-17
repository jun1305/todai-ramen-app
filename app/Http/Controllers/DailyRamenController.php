<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Models\DailyRamen; // 👈 ★ここ重要！「Daily」ではなく「DailyRamen」を使う
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Shop; 

class DailyRamenController extends Controller
{
    // ① 一覧画面（みんなが見る画面）
    public function index(Request $request)
    {
        $query = DailyRamen::query();
        
        // ① メインで表示する記事を決める（ここは変わらず）
        if ($request->has('id')) {
            $post = $query->findOrFail($request->get('id'));
        } else {
            $post = $query->latest('eaten_at')->first();
        }
    
        if (!$post) {
            return view('daily_ramens.empty');
        }
    
        // ② カレンダーの基準月を決める（ここを変更！）
        // URLに ?month=2025-11 のような指定があればその月を、なければ記事の食べた月を使う
        if ($request->has('month')) {
            $currentMonth = Carbon::parse($request->get('month'));
        } else {
            $currentMonth = $post->eaten_at->copy();
        }

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();
    
        // その月の投稿データを取得
        $monthlyPosts = DailyRamen::whereBetween('eaten_at', [$startOfMonth, $endOfMonth])
            ->orderBy('eaten_at', 'asc')
            ->get()
            ->keyBy(function($item) {
                return $item->eaten_at->format('j');
            });
    
        // 記事単位の前後ナビ（前の投稿・次の投稿ボタン用）
        $prevPost = DailyRamen::where('eaten_at', '<', $post->eaten_at)->orderBy('eaten_at', 'desc')->first();
        $nextPost = DailyRamen::where('eaten_at', '>', $post->eaten_at)->orderBy('eaten_at', 'asc')->first();

        // ③ カレンダーの前後月リンク用データ（単純に1ヶ月前・1ヶ月後）
        $prevMonth = $startOfMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $startOfMonth->copy()->addMonth()->format('Y-m');
    
        return view('daily_ramens.index', compact(
            'post', 'monthlyPosts', 'prevPost', 'nextPost', 'startOfMonth', 
            'prevMonth', 'nextMonth' // 👈 追加した変数
        ));
    }

    // ② 作成画面（会長専用）
    public function create()
    {
        if (auth()->id() !== 1) {
            abort(403, '会長専用エリアです');
        }
        // ビューの場所が daily/create.blade.php なら 'daily.create' に直す
        // もし daily_ramens フォルダを作っているならそのままでOK
        return view('daily_ramens.create'); 
    }

    // ③ 保存処理（会長専用）
    public function store(Request $request)
    {
        if (auth()->id() !== 1) abort(403);

        $request->validate([
            'shop_name' => 'required',
            'image' => 'required|image|max:10240',
            'eaten_at' => 'required|date',
            // addressなどはnullableで受け取る
        ]);

        // ▼▼▼ 賢い保存処理ここから ▼▼▼
        
        // 1. お店を探す、なければ作る
        $shop = null;

        // ① Google Place ID があれば、それで探す（一番確実）
        if ($request->google_place_id) {
            $shop = Shop::where('google_place_id', $request->google_place_id)->first();
        }

        // ② なければ、店名で探してみる
        if (!$shop) {
            $shop = Shop::where('name', $request->shop_name)->first();
        }

        // ③ それでもなければ、新規作成する
        if (!$shop) {
            $shop = Shop::create([
                'name' => $request->shop_name,
                'address' => $request->address, // 住所はShopsテーブルに保存
                'google_place_id' => $request->google_place_id,
            ]);
        }
        // ▲▲▲ 賢い保存処理ここまで ▲▲▲


        // DailyRamen の保存
        $daily = new DailyRamen();
        
        // ★紐付け: 作った(見つけた)ショップのIDを入れる
        $daily->shop_id = $shop->id;
        
        $daily->shop_name = $request->shop_name; // 予備として文字も残す
        $daily->menu_name = $request->menu_name;
        $daily->comment = $request->comment;
        $daily->eaten_at = $request->eaten_at;

        // 画像保存処理（既存のまま）
        if ($request->hasFile('image')) {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($request->file('image'));
            $image->scale(width: 800);
            $encoded = $image->toJpeg(quality: 80);
            
            $fileName = 'uploads/daily/' . Str::random(40) . '.jpg';
            
            if (!file_exists(public_path('uploads/daily'))) {
                mkdir(public_path('uploads/daily'), 0777, true);
            }
            file_put_contents(public_path($fileName), $encoded);
            $daily->image_path = $fileName;
        }

        $daily->save();

        return redirect()->route('daily.index')->with('success', '記録しました！');
    }
    
    public function fetchCalendar(Request $request)
    {
        // ① 表示する月を決める
        $monthStr = $request->get('month');
        $currentMonth = $monthStr ? Carbon::parse($monthStr) : now();

        // ② 現在表示中の記事ID（ハイライト用）
        $post = null;
        if ($request->has('id')) {
            $post = DailyRamen::find($request->get('id'));
        }

        // ③ その月のデータを取得
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $monthlyPosts = DailyRamen::whereBetween('eaten_at', [$startOfMonth, $endOfMonth])
            ->orderBy('eaten_at', 'asc')
            ->get()
            ->keyBy(function($item) {
                return $item->eaten_at->format('j');
            });

        // ④ 前月・次月の計算
        $prevMonth = $startOfMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $startOfMonth->copy()->addMonth()->format('Y-m');

        // ★さっき作った「calendar.blade.php」だけを返す
        return view('daily_ramens.calendar', compact(
            'post', 'monthlyPosts', 'startOfMonth', 'prevMonth', 'nextMonth'
        ));
    }

        // ④ 編集画面（会長専用）
    public function edit($id)
    {
        if (auth()->id() !== 1) abort(403);

        $daily = DailyRamen::findOrFail($id);
        return view('daily_ramens.edit', compact('daily'));
    }

    // ⑤ 更新処理（会長専用）
    public function update(Request $request, $id)
    {
        if (auth()->id() !== 1) abort(403);

        $request->validate([
            'shop_name' => 'required',
            'image' => 'nullable|image|max:10240', // 更新時は任意
            'eaten_at' => 'required|date',
        ]);

        $daily = DailyRamen::findOrFail($id);

        // 1. お店の更新（PostControllerと同じロジックで賢く）
        $shop = null;
        if ($request->google_place_id) {
            $shop = Shop::where('google_place_id', $request->google_place_id)->first();
        }
        if (!$shop) {
            $shop = Shop::where('name', $request->shop_name)->first();
        }
        if (!$shop) {
            $shop = Shop::create([
                'name' => $request->shop_name,
                'address' => $request->address,
                'google_place_id' => $request->google_place_id,
            ]);
        }

        // 2. データの更新
        $daily->shop_id = $shop->id;
        $daily->shop_name = $request->shop_name;
        $daily->menu_name = $request->menu_name;
        $daily->comment = $request->comment;
        $daily->eaten_at = $request->eaten_at;

        // 3. 画像の差し替え（新しい画像がある場合のみ）
        if ($request->hasFile('image')) {
            // 古い画像を削除
            if ($daily->image_path && file_exists(public_path($daily->image_path))) {
                unlink(public_path($daily->image_path));
            }

            // 新しい画像を保存
            $manager = new ImageManager(new Driver());
            $image = $manager->read($request->file('image'));
            $image->scale(width: 800);
            $encoded = $image->toJpeg(quality: 80);
            
            $fileName = 'uploads/daily/' . Str::random(40) . '.jpg';
            
            if (!file_exists(public_path('uploads/daily'))) {
                mkdir(public_path('uploads/daily'), 0777, true);
            }
            file_put_contents(public_path($fileName), $encoded);
            $daily->image_path = $fileName;
        }

        $daily->save();

        // 詳細ページ（その日のページ）にリダイレクト
        return redirect()->route('daily.index', ['id' => $daily->id])->with('success', '更新しました！');
    }
}