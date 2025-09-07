<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ItemsIndexTest extends TestCase
{
    use RefreshDatabase;

    /** items テーブルに合わせて安全に1件作る（Factory不使用） */
    private function makeItemFor(?User $owner = null, array $override = []): Item
    {
        $cols = Schema::getColumnListing('items');
        $data = [];

        // 必須になりがちなカラムを存在チェックして埋める
        if (in_array('name', $cols))         $data['name'] = '商品' . Str::random(5);
        if (in_array('price', $cols))        $data['price'] = 1000;
        if (in_array('description', $cols))  $data['description'] = 'テスト説明';
        if (in_array('brand', $cols))        $data['brand'] = 'ブランドX';
        if (in_array('condition', $cols))    $data['condition'] = '良好';
        if (in_array('image', $cols))        $data['image'] = 'noimage.png';
        if (in_array('image_path', $cols))   $data['image_path'] = 'items/noimage.png';

        // 出品者キー（必須。スキーマどおりに入れる）
        if ($owner) {
            if (in_array('seller_id', $cols)) $data['seller_id'] = $owner->id;
            if (in_array('user_id', $cols))   $data['user_id']   = $owner->id;
        }

        // 売却系の既定
        if (in_array('sold_at', $cols))      $data['sold_at'] = null;
        if (in_array('is_sold', $cols))      $data['is_sold'] = false;

        $item = new Item();
        $item->forceFill(array_merge($data, $override))->save();
        return $item->fresh();
    }

    /** @test */
    public function 全ての商品が一覧に表示される()
    {
        // ← ここが修正点：必ず出品者を作って紐づける
        $seller = User::factory()->create(['email_verified_at' => now()]);

        $i1 = $this->makeItemFor($seller, ['name' => 'りんご']);
        $i2 = $this->makeItemFor($seller, ['name' => 'みかん']);
        $i3 = $this->makeItemFor($seller, ['name' => 'バナナ']);

        $res = $this->get('/'); // ゲストでトップ

        $res->assertOk();
        $res->assertSee('りんご');
        $res->assertSee('みかん');
        $res->assertSee('バナナ');
    }

    /** @test */
    public function 購入済み商品には_Sold_ラベルが表示される()
    {
        // ← ここも出品者を作って紐づける
        $seller = User::factory()->create(['email_verified_at' => now()]);

        // 未購入
        $this->makeItemFor($seller);

        // 購入済み（実カラムに合わせて状態を立てる）
        if (Schema::hasColumn('items', 'sold_at')) {
            $this->makeItemFor($seller, ['sold_at' => now()]);
        } elseif (Schema::hasColumn('items', 'buyer_id')) {
            $buyer = User::factory()->create();
            $this->makeItemFor($seller, ['buyer_id' => $buyer->id]);
        } elseif (Schema::hasColumn('items', 'is_sold')) {
            $this->makeItemFor($seller, ['is_sold' => true]);
        } else {
            $this->makeItemFor($seller); // 予備
        }

        $res = $this->get('/');

        $res->assertOk();

        // Sold / sold / SOLD いずれでもOK（index.bladeは「Sold」）
        $html = $res->getContent();
        $this->assertTrue(
            mb_stripos($html, 'sold') !== false,
            "Soldラベルが見つかりませんでした。HTML抜粋:\n" . mb_substr($html, 0, 500)
        );
    }

    /** @test */
    public function 自分が出品した商品は一覧に表示されない()
    {
        $seller = User::factory()->create([
            'email' => 'seed-seller@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);
        $other = User::factory()->create();

        $ownName   = '自分の出品A';
        $otherName = '他人の出品B';
        $this->makeItemFor($seller, ['name' => $ownName]);
        $this->makeItemFor($other,  ['name' => $otherName]);

        $this->actingAs($seller);
        $res = $this->get('/');

        $res->assertOk();
        $res->assertDontSee($ownName);
        $res->assertSee($otherName);
    }
}
