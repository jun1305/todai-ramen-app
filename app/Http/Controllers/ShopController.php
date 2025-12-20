<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shop;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        // ▼▼▼ 修正1: $query を使い回さず、リスト用はリスト用で作る ▼▼▼
        $shopsQuery = Shop::query(); // 名前を変更 ($query -> $shopsQuery)
        
        if ($search) {
            $shopsQuery->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('address', 'like', '%' . $search . '%');
            });
        }

        // 2. リスト表示用
        $shops = $shopsQuery->with('latestPost')
            ->orderBy('posts_count', 'desc') // メインの並び順
            ->orderBy('id', 'desc')          // ★追加：同率のときはID順（これで順序が固定される）
            ->paginate(10);

        // 3. ピックアップ用（検索時は空）
        $pickupShops = collect();
        if (!$search) {
            // ▼▼▼ 修正2: 完全に別のクエリとして定義 ▼▼▼
            $pickupShops = Shop::whereHas('posts')
                ->with('latestPost')
                ->inRandomOrder()
                ->limit(5)
                ->get();
        }

        return view('shops.index', compact('shops', 'pickupShops', 'search'));
    }

    // showメソッドは変更なしなので省略...
    public function show($id)
    {
        $shop = Shop::withCount('posts')->with(['latestPost'])->findOrFail($id);
        $posts = $shop->posts()->with('user')->latest('eaten_at')->paginate(10);

        // ★修正: 5件制限を撤廃。1件でもあれば計算、なければ null
        $avgScore = $shop->posts()->avg('score');
        if ($avgScore) {
            $avgScore = round($avgScore, 1);
        }

        return view('shops.show', compact('shop', 'posts', 'avgScore'));
    }
}