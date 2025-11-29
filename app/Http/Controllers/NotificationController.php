<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    // 全て既読にする（赤丸を消す）
    public function markAsRead()
    {
        // ログイン中のユーザーの「未読の通知」を全て「既読済み」にする魔法のコマンド
        Auth::user()->unreadNotifications->markAsRead();
        
        return response()->json(['success' => true]);
    }
}