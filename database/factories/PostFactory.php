<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
{
    return [
        'user_id' => \App\Models\User::factory(),
        'shop_id' => \App\Models\Shop::inRandomOrder()->first()->id ?? \App\Models\Shop::factory(), // お店があればランダムに
        'score' => fake()->numberBetween(1, 5),
        'comment' => fake()->realText(20),
        'eaten_at' => fake()->dateTimeBetween('-1 year', 'now'),
        // 画像はnullでも動くようにしておく
        'image_path' => null, 
    ];
}
}
