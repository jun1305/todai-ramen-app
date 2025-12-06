<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run()
{
    // ユーザーを適当に作成（もし必要なら）
    // \App\Models\User::factory(10)->create();

    // 既存のユーザーID 1番の人向けに、過去の投稿を10個作る
    \App\Models\Post::factory(10)->create([
        'user_id' => 1, // 自分のIDに合わせてください
        // eaten_at を「過去1年間」のランダムな日時にする
        'eaten_at' => fake()->dateTimeBetween('-1 year', 'now'),
    ]);
}
}
