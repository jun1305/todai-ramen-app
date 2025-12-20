<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Shop;

class QuizController extends Controller
{
    public function index()
    {
        // 修正点: 「投稿」ではなく「お店」を基準にランダムに5件選ぶ
        // 条件: 画像付きの投稿(posts)を持っているお店であること
        $targetShops = Shop::whereHas('posts', function($query) {
                $query->whereNotNull('image_path');
            })
            ->inRandomOrder()
            ->limit(5)
            ->get();

        // 5店舗未満ならエラー
        if ($targetShops->count() < 5) {
            return redirect()->route('home')->with('error', 'クイズを作るための投稿（お店の数）がまだ足りません！');
        }

        // Vue/Alpine.js 用データ作成
        $questions = $targetShops->map(function ($shop) {
            
            // そのお店の画像付き投稿から、ランダムに1枚選ぶ
            $post = $shop->posts()
                ->whereNotNull('image_path')
                ->inRandomOrder()
                ->first();

            // ダミーの選択肢を3つ取得（正解の店以外からランダム）
            $wrongShops = Shop::where('id', '!=', $shop->id)
                ->inRandomOrder()
                ->limit(3)
                ->get();

            // 選択肢をマージ
            $options = collect();
            $options->push(['name' => $shop->name, 'is_correct' => true]);
            
            foreach ($wrongShops as $wrongShop) {
                $options->push(['name' => $wrongShop->name, 'is_correct' => false]);
            }

            return [
                'image_url' => asset($post->image_path),
                'options' => $options->shuffle()->values(),
                'correct_name' => $shop->name,
            ];
        });

        return view('quiz.index', [
            'questions' => $questions
        ]);
    }
}