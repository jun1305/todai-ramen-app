<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;

class BookmarkController extends Controller
{
    // 一覧表示
    public function index()
    {
        $user = Auth::user();
        // ユーザーがブックマークしたお店を取得（ページネーション付き）
        $shops = $user->bookmarks()->with('latestPost')->paginate(20);

        return view('bookmarks.index', compact('shops'));
    }

    // 追加・解除（トグル）
    public function toggle(Shop $shop)
    {
        $user = Auth::user();
        
        // toggleメソッドは、あれば削除、なければ追加を自動でやってくれます
        $result = $user->bookmarks()->toggle($shop->id);

        // 結果を返す（attachedにIDが入っていれば「追加された」、空なら「削除された」）
        $status = !empty($result['attached']);

        return response()->json(['bookmarked' => $status]);
    }
}