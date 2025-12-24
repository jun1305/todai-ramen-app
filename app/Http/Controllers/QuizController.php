<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Shop;

class QuizController extends Controller
{
    public function index()
    {
        // クイズの正解となるお店を5件取得
        // ★最適化: N+1問題を防ぐため genres も一緒にロードしておく
        $targetShops = Shop::with('genres') 
            ->whereHas('posts', function($query) {
                $query->whereNotNull('image_path');
            })
            ->inRandomOrder()
            ->limit(5)
            ->get();

        if ($targetShops->count() < 5) {
            return redirect()->route('home')->with('error', 'クイズを作るための投稿（お店の数）がまだ足りません！');
        }

        $questions = $targetShops->map(function ($shop) {
            
            // ① 画像の取得
            $post = $shop->posts()
                ->whereNotNull('image_path')
                ->inRandomOrder()
                ->first();

            // =============================================
            // ② 選択肢（ハズレ枠）の生成ロジック変更
            // =============================================
            
            // 正解のお店のジャンルIDリストを取得
            $genreIds = $shop->genres->pluck('id');
            
            $wrongShops = collect();

            // A. まずは「同じジャンル」のお店を探す
            if ($genreIds->isNotEmpty()) {
                $wrongShops = Shop::where('id', '!=', $shop->id)
                    ->whereHas('genres', function($q) use ($genreIds) {
                        $q->whereIn('genres.id', $genreIds);
                    })
                    ->inRandomOrder()
                    ->limit(3)
                    ->get();
            }

            // B. 足りない分を「その他のお店」から埋める
            $neededCount = 3 - $wrongShops->count();

            if ($neededCount > 0) {
                // 除外IDリスト（正解の店 + すでに選ばれた同ジャンルの店）
                $excludeIds = $wrongShops->pluck('id')->push($shop->id);

                $fillers = Shop::whereNotIn('id', $excludeIds)
                    ->inRandomOrder()
                    ->limit($neededCount)
                    ->get();
                
                // コレクションを結合
                $wrongShops = $wrongShops->merge($fillers);
            }
            // =============================================

            // 選択肢データの整形
            $options = collect();
            // 正解
            $options->push(['name' => $shop->name, 'is_correct' => true]);
            // 不正解
            foreach ($wrongShops as $wrongShop) {
                $options->push(['name' => $wrongShop->name, 'is_correct' => false]);
            }

            return [
                'image_url' => asset($post->image_path),
                'options' => $options->shuffle()->values(), // シャッフル
                'correct_name' => $shop->name,
            ];
        });

        return view('quiz.index', [
            'questions' => $questions
        ]);
    }
}