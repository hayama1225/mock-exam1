<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemsIndexTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 全商品を取得できる_商品名が出る()
    {
        $seller = User::factory()->create();
        Item::create([
            'name' => 'テスト商品A',
            'price' => 1000,
            'image' => 'default.png',
            'description' => '説明',
            'condition' => 'new',
            'seller_id' => $seller->id,
        ]);
        Item::create([
            'name' => 'テスト商品B',
            'price' => 2000,
            'image' => 'default.png',
            'description' => '説明',
            'condition' => 'new',
            'seller_id' => $seller->id,
        ]);

        $this->withoutMiddleware('force.profile');
        $res = $this->get('/');

        $res->assertOk()
            ->assertSee('テスト商品A')
            ->assertSee('テスト商品B');
    }
}
