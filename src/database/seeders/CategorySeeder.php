<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // 既存の「本・マンガ」を「本」へリネーム（あれば）
        Category::where('name', '本・マンガ')->update(['name' => '本']);

        $names = [
            'ファッション',
            '家電',
            'インテリア',
            'レディース',
            'メンズ',
            'コスメ',
            '本',
            'ゲーム',
            'スポーツ',
            'キッチン',
            'ハンドメイド',
            'アクセサリー',
            'おもちゃ',
            'ベビー・キッズ',
        ];

        foreach ($names as $n) {
            Category::firstOrCreate(['name' => $n]);
        }
    }
}
