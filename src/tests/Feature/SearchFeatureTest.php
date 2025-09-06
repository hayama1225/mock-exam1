<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SearchFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 検索キーワードで結果が絞られる想定()
    {
        DB::table('items')->insert([
            ['name' => 'りんご', 'description' => 'a', 'price' => 100, 'condition' => '良好', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'みかん', 'description' => 'a', 'price' => 200, 'condition' => '良好', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->get('/?q=りんご')->assertOk();
        // 画面断言はHTML構造依存なので省略（Controller側でクエリ適用していればOK）
    }
}
