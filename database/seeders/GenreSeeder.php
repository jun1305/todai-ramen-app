<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Genre;

class GenreSeeder extends Seeder
{
    public function run()
    {
        $genres = [
            '醤油', '塩', '味噌', '豚骨', '豚骨魚介', 
            '家系', '二郎系', 'つけ麺', '油そば/まぜそば', 
            '鶏白湯', '煮干し', '担々麺', '創作/その他'
        ];

        foreach ($genres as $name) {
            Genre::firstOrCreate(['name' => $name]);
        }
    }
}