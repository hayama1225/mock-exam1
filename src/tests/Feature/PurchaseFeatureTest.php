<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class PurchaseFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** items テーブルに合わせて安全に1件作る（Factory不使用） */
    private function makeItemFor(User $owner, array $override = []): Item
    {
        $cols = Schema::getColumnListing('items');
        $data = [
            'seller_id'   => $owner->id,
            'name'        => '購入テスト_' . Str::random(5),
            'brand'       => 'BRAND-P',
            'description' => 'desc',
            'price'       => 1200,
            'condition'   => '良好',
            'image_path'  => 'items/noimage.png',
            'sold_at'     => null,
            'buyer_id'    => null,
        ];
        $data = array_intersect_key($data, array_flip($cols));
        $item = new Item();
        $item->forceFill(array_merge($data, $override))->save();
        return $item->fresh();
    }

    /** force.profile 通過用（存在しない環境では何もしない） */
    private function ensureProfileCompleted(User $user): void
    {
        if (!Schema::hasTable('profiles')) return;

        $cols = Schema::getColumnListing('profiles');
        $payload = ['user_id' => $user->id];
        if (in_array('profile_completed_at', $cols)) {
            $payload['profile_completed_at'] = now();
        }
        $exists = DB::table('profiles')->where('user_id', $user->id)->exists();
        $exists
            ? DB::table('profiles')->where('user_id', $user->id)->update($payload)
            : DB::table('profiles')->insert($payload);
    }

    /**
     * 購入完了後の状態をDBに反映。
     * - items: buyer_id / sold_at を更新
     * - purchases: enum 制約に合わせて1行作成（payment_method='card', status='paid'）
     */
    private function markAsPurchased(Item $item, User $buyer): void
    {
        // items を購入済みに
        $updates = [];
        if (Schema::hasColumn('items', 'buyer_id')) $updates['buyer_id'] = $buyer->id;
        if (Schema::hasColumn('items', 'sold_at'))  $updates['sold_at']  = now();
        if ($updates) {
            DB::table('items')->where('id', $item->id)->update($updates);
        }

        // purchases 行を作成（テーブルがある場合）
        if (Schema::hasTable('purchases')) {
            $cols = Schema::getColumnListing('purchases');
            $row  = ['item_id' => $item->id, 'buyer_id' => $buyer->id];

            if (in_array('amount', $cols))               $row['amount'] = $item->price ?? 1200;
            if (in_array('payment_method', $cols))       $row['payment_method'] = 'card';   // ✔ enum 許可値
            if (in_array('zip', $cols))                  $row['zip'] = '1000000';
            if (in_array('address', $cols))              $row['address'] = '東京都テスト区0-0';
            if (in_array('building', $cols))             $row['building'] = 'テストビル';
            if (in_array('stripe_session_id', $cols))    $row['stripe_session_id'] = 'test_session';
            if (in_array('stripe_payment_intent', $cols)) $row['stripe_payment_intent'] = 'test_intent';
            if (in_array('status', $cols))               $row['status'] = 'paid';           // ✔ enum 許可値
            if (in_array('paid_at', $cols))              $row['paid_at'] = now();

            DB::table('purchases')->insert($row);
        }
    }

    /** @test */
    public function 購入ボタン押下で購入フローが開始できる()
    {
        $seller  = User::factory()->create();
        $buyer   = User::factory()->create(['email_verified_at' => now()]);
        $this->ensureProfileCompleted($buyer);
        $item    = $this->makeItemFor($seller, ['name' => '開始確認アイテム']);

        $this->actingAs($buyer);

        $show = $this->get(route('purchase.show', $item));
        $show->assertOk();

        $start = $this->post(route('purchase.submit', $item));
        $start->assertStatus(302); // 外部(Stripe)へリダイレクト
    }

    /** @test */
    public function 購入済み商品は一覧で_Sold_表示される()
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create(['email_verified_at' => now()]);
        $this->ensureProfileCompleted($buyer);

        $item = $this->makeItemFor($seller, ['name' => 'Sold表示アイテム']);

        // 購入完了状態へ
        $this->markAsPurchased($item, $buyer);

        $res = $this->get('/'); // 商品一覧
        $res->assertOk();
        $res->assertSee('Sold');               // バッジ
        $res->assertSee('Sold表示アイテム');    // 商品名
    }

    /** @test */
    public function 購入済み商品が_マイページ_の購入一覧に出る()
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create(['email_verified_at' => now()]);
        $this->ensureProfileCompleted($buyer);

        $item = $this->makeItemFor($seller, ['name' => '購入一覧アイテム']);

        $this->markAsPurchased($item, $buyer);

        $this->actingAs($buyer);
        $mypage = $this->get(route('mypage.index'));
        $mypage->assertOk();
        $mypage->assertSee('購入一覧アイテム');
    }
}
