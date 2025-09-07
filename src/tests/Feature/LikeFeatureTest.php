<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class LikeFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** itemsテーブルに合わせて安全に1件作る（Factory不使用） */
    private function makeItemFor(User $owner, array $override = []): Item
    {
        $cols = Schema::getColumnListing('items');
        $data = [
            'seller_id'   => $owner->id,
            'name'        => 'いいねテスト商品_' . Str::random(5),
            'brand'       => 'BRAND-X',
            'description' => 'desc',
            'price'       => 1000,
            'condition'   => '良好',
            'image_path'  => 'items/noimage.png',
            'sold_at'     => null,
        ];
        $data = array_intersect_key($data, array_flip($cols));
        $item = new Item();
        $item->forceFill(array_merge($data, $override))->save();
        return $item->fresh();
    }

    /** force.profile を通すため、profiles があれば完了状態を作る（存在しない環境でも安全に no-op） */
    private function ensureProfileCompleted(User $user): void
    {
        if (!Schema::hasTable('profiles')) return;

        $cols = Schema::getColumnListing('profiles');
        $payload = ['user_id' => $user->id];
        if (in_array('profile_completed_at', $cols)) {
            $payload['profile_completed_at'] = now();
        }
        // 既にあれば更新、無ければ作成
        $exists = DB::table('profiles')->where('user_id', $user->id)->exists();
        if ($exists) {
            DB::table('profiles')->where('user_id', $user->id)->update($payload);
        } else {
            DB::table('profiles')->insert($payload);
        }
    }

    /** @test */
    public function いいねするとマイリストに登録されアイコンが_fillになる()
    {
        $seller = User::factory()->create();
        $liker  = User::factory()->create(['email_verified_at' => now()]);
        $this->ensureProfileCompleted($liker);

        $item = $this->makeItemFor($seller, ['name' => '苺ジャム']);

        // 初期状態（未ログイン表示や未いいね状態でもOK）
        $this->actingAs($liker);

        // トグル（追加）
        $res = $this->post(route('items.like', $item));
        $res->assertStatus(302);

        // likes ピボットに登録されたか
        $this->assertDatabaseHas('likes', [
            'user_id' => $liker->id,
            'item_id' => $item->id,
        ]);

        // マイリストに表示される（= 登録済）
        $res = $this->get('/?tab=mylist&q=苺');
        $res->assertOk();
        $res->assertSee('苺ジャム');

        // 詳細ページでは filled アイコンが出る
        $res = $this->get(route('items.show', $item));
        $res->assertOk();
        $res->assertSee('img/icons/like_fill.svg');
    }

    /** @test */
    public function 再度押下でいいね解除されアイコンが通常に戻る()
    {
        $seller = User::factory()->create();
        $liker  = User::factory()->create(['email_verified_at' => now()]);
        $this->ensureProfileCompleted($liker);

        $item = $this->makeItemFor($seller, ['name' => '林檎ソーダ']);

        // まず付与しておく
        $this->actingAs($liker);
        $this->post(route('items.like', $item)); // 追加
        $this->assertDatabaseHas('likes', ['user_id' => $liker->id, 'item_id' => $item->id]);

        // もう一度で解除
        $res = $this->post(route('items.like', $item));
        $res->assertStatus(302);

        $this->assertDatabaseMissing('likes', [
            'user_id' => $liker->id,
            'item_id' => $item->id,
        ]);

        // マイリストからは消える
        $res = $this->get('/?tab=mylist&q=林檎');
        $res->assertOk();
        $res->assertDontSee('林檎ソーダ');

        // 詳細ページでは通常アイコン
        $res = $this->get(route('items.show', $item));
        $res->assertOk();
        $res->assertSee('img/icons/like.svg');
        $res->assertDontSee('img/icons/like_fill.svg');
    }
}
