<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ItemShowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ゲストは商品詳細を閲覧できる()
    {
        // items テーブルに最小列で挿入（必要に応じてカラム名をあなたのスキーマに合わせる）
        $itemId = DB::table('items')->insertGetId([
            'name' => 'テスト商品',
            'description' => '説明',
            'price' => 1000,
            'condition' => '良好',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $res = $this->get('/item/' . $itemId);
        $res->assertOk();
    }

    /** @test */
    public function 存在しない商品IDは404を返す()
    {
        $res = $this->get('/item/999999');
        $res->assertNotFound();
    }
}
