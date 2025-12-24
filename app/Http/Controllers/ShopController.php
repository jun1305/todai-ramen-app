<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shop;
use App\Models\Genre;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;
        // ▼▼▼ 変更: 配列で受け取る (なければ空配列) ▼▼▼
        $genreIds = $request->input('genre_ids', []);

        $shopsQuery = Shop::query();
        
        // キーワード検索
        if ($search) {
            $shopsQuery->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('address', 'like', '%' . $search . '%');
            });
        }

        // ▼▼▼ 変更: 複数ジャンル絞り込み (OR検索) ▼▼▼
        // 「選んだジャンルのうち、どれか1つでも当てはまればOK」というロジック
        if (!empty($genreIds)) {
            $shopsQuery->whereHas('genres', function($q) use ($genreIds) {
                $q->whereIn('genres.id', $genreIds);
            });
        }
        // ▲▲▲ 変更ここまで ▲▲▲

        // リスト表示用
        $shops = $shopsQuery->with(['latestPost', 'genres'])
            ->orderBy('posts_count', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10);

        // 検索条件をページネーションリンクに追加
        $shops->appends($request->all());

        // ピックアップ用（検索・絞り込み時は空）
        $pickupShops = collect();
        if (!$search && empty($genreIds)) { // 条件追加
            $pickupShops = Shop::whereHas('posts')
                ->with('latestPost')
                ->inRandomOrder()
                ->limit(5)
                ->get();
        }

        $genres = Genre::all();

        // ビューに genreIds も渡す
        return view('shops.index', compact('shops', 'pickupShops', 'search', 'genres', 'genreIds'));
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

    // ④ 編集画面 (管理者専用)
    public function edit(Shop $shop)
    {
        // ★権限チェック: ID=1のユーザーだけ許可（必要に応じて変更してください）
        if (auth()->id() !== 1) {
            abort(403, '管理者専用ページです');
        }

        $genres = Genre::all();
        return view('shops.edit', compact('shop', 'genres'));
    }

    // ⑤ 更新処理 (管理者専用)
    public function update(Request $request, Shop $shop)
    {
        if (auth()->id() !== 1) {
            abort(403, '管理者専用ページです');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'google_place_id' => 'nullable|string|max:255',
            'genres' => 'nullable|array',
            'genres.*' => 'exists:genres,id',
        ]);

        // お店情報の更新
        $shop->update([
            'name' => $validated['name'],
            'address' => $validated['address'],
            'google_place_id' => $validated['google_place_id'],
        ]);

        // ジャンルの更新 (syncを使うと、選択したものだけに置き換わります)
        if (isset($validated['genres'])) {
            $shop->genres()->sync($validated['genres']);
        } else {
            // チェックが全部外された場合は全削除
            $shop->genres()->detach();
        }

        return redirect()->route('shops.show', $shop)->with('success', 'お店情報を更新しました！');
    }
}