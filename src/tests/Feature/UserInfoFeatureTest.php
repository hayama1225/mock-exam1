<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserInfoFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function makeItemFor(User $owner, array $override = []): Item
    {
        $cols = Schema::getColumnListing('items');
        $data = [
            'seller_id'   => $owner->id,
            'name'        => 'ITEM_' . Str::random(6),
            'brand'       => 'BRAND',
            'description' => 'desc',
            'price'       => 1000,
            'condition'   => '良好',
            'image_path'  => 'items/noimage.png',
            'sold_at'     => null,
            'buyer_id'    => null,
        ];
        $data = array_intersect_key($data, array_flip($cols));
        $item = new \App\Models\Item();
        $item->forceFill(array_merge($data, $override))->save();
        return $item->fresh();
    }

    private function ensureProfile(User $user, array $over = []): void
    {
        if (!Schema::hasTable('profiles')) return;
        $base = [
            'user_id' => $user->id,
            'username' => 'テスト太郎',
            'zip' => '1000001',
            'address' => '東京都千代田区1-1',
            'building' => 'テストビル',
            'avatar_path' => 'avatars/taro.png',
            'profile_completed_at' => now(),
        ];
        $row = array_merge($base, $over);
        $exists = DB::table('profiles')->where('user_id', $user->id)->exists();
        $exists
            ? DB::table('profiles')->where('user_id', $user->id)->update($row)
            : DB::table('profiles')->insert($row);
    }

    /** @test */
    public function プロフィール画像_ユーザー名_出品一覧が表示される()
    {
        $user = User::factory()->create(['name' => 'ユーザーA', 'email_verified_at' => now()]);
        $this->ensureProfile($user, ['username' => 'ユーザーA']);

        // 出品した商品を2件
        $sell1 = $this->makeItemFor($user, ['name' => '出品A']);
        $sell2 = $this->makeItemFor($user, ['name' => '出品B']);

        $this->actingAs($user);

        $res = $this->get(route('mypage.index', ['tab' => 'sell']));
        $res->assertOk();

        // プロフィール画像とユーザー名（username or users.name のどちらでも通るように）
        $html = $res->getContent();
        $this->assertTrue(
            str_contains($html, 'avatars/taro.png') || str_contains($html, 'storage/avatars/taro.png'),
            'プロフィール画像パスが表示されていません'
        );
        $this->assertTrue(
            str_contains($html, 'ユーザーA') || str_contains($html, $user->name),
            'ユーザー名が表示されていません'
        );

        // 出品一覧
        $res->assertSee('出品A');
        $res->assertSee('出品B');
    }

    /** @test */
    public function 購入した商品一覧が表示される()
    {
        $buyer   = User::factory()->create(['name' => '購入者', 'email_verified_at' => now()]);
        $this->ensureProfile($buyer, ['username' => '購入者']);
        $seller  = User::factory()->create();

        // 購入済み2件（items 側を購入済みに）
        $bought1 = $this->makeItemFor($seller, ['name' => '購入品A']);
        $bought2 = $this->makeItemFor($seller, ['name' => '購入品B']);
        if (Schema::hasColumn('items', 'buyer_id')) {
            DB::table('items')->whereKey([$bought1->id, $bought2->id])->update(['buyer_id' => $buyer->id]);
        }
        if (Schema::hasColumn('items', 'sold_at')) {
            DB::table('items')->whereKey([$bought1->id, $bought2->id])->update(['sold_at' => now()]);
        }

        // ★ 購入一覧は purchases 起点なので、レコードを作成する
        if (Schema::hasTable('purchases')) {
            $now = now();
            DB::table('purchases')->insert([
                [
                    'item_id'         => $bought1->id,
                    'buyer_id'        => $buyer->id,
                    'amount'          => $bought1->price ?? 1000,
                    'payment_method'  => 'card',          // enumに適合
                    'zip'             => '100-0001',      // NOT NULL
                    'address'         => '東京都千代田区1-1',
                    'building'        => 'テストビル',
                    'status'          => 'paid',          // 既定の一覧要件に合わせて paid
                    'paid_at'         => $now,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ],
                [
                    'item_id'         => $bought2->id,
                    'buyer_id'        => $buyer->id,
                    'amount'          => $bought2->price ?? 1000,
                    'payment_method'  => 'card',
                    'zip'             => '100-0001',
                    'address'         => '東京都千代田区1-1',
                    'building'        => 'テストビル',
                    'status'          => 'paid',
                    'paid_at'         => $now,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ],
            ]);
        }

        $this->actingAs($buyer);

        // マイページの購入タブ
        $res = $this->get(route('mypage.index', ['tab' => 'buy']));
        $res->assertOk();
        $res->assertSee('購入品A');
        $res->assertSee('購入品B');
    }
}
