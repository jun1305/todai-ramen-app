<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\Shop;

class AdminCampaignController extends Controller
{
    // 管理画面表示
    public function index()
    {
        $campaigns = Campaign::with('shop')->latest()->get();
        $shops = Shop::all(); 
        return view('admin.index', compact('campaigns', 'shops'));
    }

    // キャンペーン登録
    public function store(Request $request)
    {
        $request->validate([
            'shop_id' => 'required',
        ]);

        // 店名を取得
        $shop = Shop::findOrFail($request->shop_id);

        Campaign::create([
            'shop_id' => $request->shop_id,
            // タイトルが入力されていればそれを、空なら店名を使う
            'title' => $request->title ?? $shop->name,
            'content' => '', // 空でOK
            'multiplier' => 2, // 一律2倍
            'is_active' => true,
        ]);

        return back()->with('success', 'キャンペーンを開始しました');
    }

    // ★★★ これが足りていませんでした！追加してください ★★★
    public function destroy($id)
    {
        Campaign::destroy($id);
        return back()->with('success', 'キャンペーンを終了しました');
    }
}