<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class SearchFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** items テーブルに合わせて安全に1件作る（Factory不使用） */
    private function makeItemFor(?User $owner = null, array $override = []): Item
    {
        $cols = Schema::getColumnListing('items');
        $data = [];

        if (in_array('name', $cols))        $data['name'] = '商品' . Str::random(5);
        if (in_array('price', $cols))       $data['price'] = 1000;
        if (in_array('description', $cols)) $data['description'] = 'テスト説明';
        if (in_array('brand', $cols))       $data['brand'] = 'ブランドX';
        if (in_array('condition', $cols))   $data['condition'] = '良好';
        if (in_array('image_path', $cols))  $data['image_path'] = 'items/noimage.png';

        if ($owner) {
            if (in_array('seller_id', $cols)) $data['seller_id'] = $owner->id;
            if (in_array('user_id', $cols))   $data['user_id']   = $owner->id;
        }

        if (in_array('sold_at', $cols))     $data['sold_at'] = null;

        $item = new Item();
        $item->forceFill(array_merge($data, $override))->save();
        return $item->fresh();
    }

    /** @test */
    public function 商品名で部分一致検索ができる()
    {
        $seller = User::factory()->create();

        $this->makeItemFor($seller, ['name' => 'りんごジュース']);
        $this->makeItemFor($seller, ['name' => 'みかん']);
        $this->makeItemFor($seller, ['name' => 'バナナ']);

        // 「りん」で部分一致（りんごだけヒット想定）
        $res = $this->get('/?q=りん');

        $res->assertOk();
        $res->assertSee('りんごジュース');
        $res->assertDontSee('みかん');
        $res->assertDontSee('バナナ');
    }

    /** @test */
    public function 検索状態がマイリストでも保持されている()
    {
        $seller = User::factory()->create();
        $user   = User::factory()->create();

        $apple  = $this->makeItemFor($seller, ['name' => 'りんごジュース']);
        $banana = $this->makeItemFor($seller, ['name' => 'バナナ']);

        // マイリストに2件いいね
        $user->likedItems()->attach([$apple->id, $banana->id]);

        // ログインして mylist に遷移。q=りん を保持したまま
        $this->actingAs($user);
        $res = $this->get('/?tab=mylist&q=りん');

        $res->assertOk();
        $res->assertSee('りんごジュース'); // q=りん に一致
        $res->assertDontSee('バナナ');      // 一致しないので出ない
    }
}
