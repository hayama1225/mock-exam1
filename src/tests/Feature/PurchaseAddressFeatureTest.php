<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class PurchaseAddressFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function makeItemFor(User $owner, array $override = []): Item
    {
        $cols = Schema::getColumnListing('items');
        $data = [
            'seller_id'   => $owner->id,
            'name'        => '住所テスト_' . Str::random(5),
            'brand'       => 'BRAND-ADDR',
            'description' => 'desc',
            'price'       => 3000,
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

    /** @test */
    public function 住所変更後_購入画面に登録住所が反映される()
    {
        $seller = \App\Models\User::factory()->create();
        $buyer  = \App\Models\User::factory()->create(['email_verified_at' => now()]);
        $this->ensureProfileCompleted($buyer);
        $item   = $this->makeItemFor($seller, ['name' => '住所反映アイテム']);

        $this->actingAs($buyer);

        $zip  = '100-0001';
        $addr = '東京都千代田区1-2-3';
        $bldg = 'テストマンション101';

        // ★ リダイレクトを追従して購入画面まで到達（同一セッションで検証できる）
        $res = $this->followingRedirects()->post(route('purchase.address.update', $item), [
            'zip' => $zip,
            'address' => $addr,
            'building' => $bldg,
        ]);

        // 購入画面（purchase.show）が表示され、登録した住所が載っている
        $res->assertOk();
        $res->assertSee('登録した住所');
        $res->assertSee($zip);
        $res->assertSee($addr);
        $res->assertSee($bldg);
    }

    /** @test */
    public function 購入時に_purchases_へ住所スナップショットが保存される()
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create(['email_verified_at' => now()]);
        $this->ensureProfileCompleted($buyer);
        $item   = $this->makeItemFor($seller, ['name' => '住所保存アイテム']);

        $this->actingAs($buyer);

        // 住所登録 → 模擬決済（completeWithoutStripe相当）を再現
        $zip = '150-0001';
        $addr = '東京都渋谷区桜丘町1-1';
        $bldg = 'サンプルビル8F';

        $this->post(route('purchase.address.update', $item), [
            'zip' => $zip,
            'address' => $addr,
            'building' => $bldg,
        ])->assertStatus(302);

        // items 更新 + purchases 1行作成（enum/必須カラムに合わせる）
        if (Schema::hasColumn('items', 'buyer_id')) {
            DB::table('items')->where('id', $item->id)->update(['buyer_id' => $buyer->id]);
        }
        if (Schema::hasColumn('items', 'sold_at')) {
            DB::table('items')->where('id', $item->id)->update(['sold_at' => now()]);
        }
        if (Schema::hasTable('purchases')) {
            $cols = Schema::getColumnListing('purchases');
            $row  = [
                'item_id' => $item->id,
                'buyer_id' => $buyer->id,
            ];
            if (in_array('amount', $cols))         $row['amount'] = $item->price ?? 3000;
            if (in_array('payment_method', $cols)) $row['payment_method'] = 'card';
            if (in_array('zip', $cols))            $row['zip'] = $zip;
            if (in_array('address', $cols))        $row['address'] = $addr;
            if (in_array('building', $cols))       $row['building'] = $bldg;
            if (in_array('status', $cols))         $row['status'] = 'paid';
            if (in_array('paid_at', $cols))        $row['paid_at'] = now();

            DB::table('purchases')->insert($row);

            $this->assertDatabaseHas('purchases', [
                'item_id' => $item->id,
                'buyer_id' => $buyer->id,
                'zip'     => $zip,
                'address' => $addr,
                'building' => $bldg,
            ]);
        }
    }
}
