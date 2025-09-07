<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentMethodSelectTest extends TestCase
{
    use RefreshDatabase;

    /** items を安全に1件作成（Factory不使用） */
    private function makeItemFor(User $owner, array $override = []): Item
    {
        $cols = Schema::getColumnListing('items');
        $data = [
            'seller_id'   => $owner->id,
            'name'        => '支払い方法テスト_' . Str::random(5),
            'brand'       => 'BRAND-PM',
            'description' => 'desc',
            'price'       => 2000,
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

    /** force.profile を通す（テーブルが無ければ何もしない） */
    private function ensureProfileCompleted(User $user): void
    {
        if (!Schema::hasTable('profiles')) return;
        $cols = Schema::getColumnListing('profiles');
        $payload = ['user_id' => $user->id];
        if (in_array('profile_completed_at', $cols)) $payload['profile_completed_at'] = now();

        $exists = DB::table('profiles')->where('user_id', $user->id)->exists();
        $exists
            ? DB::table('profiles')->where('user_id', $user->id)->update($payload)
            : DB::table('profiles')->insert($payload);
    }

    /** @test */
    public function コンビニを選択すると反映される()
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create(['email_verified_at' => now()]);
        $this->ensureProfileCompleted($buyer);
        $item   = $this->makeItemFor($seller, ['name' => 'コンビニ決済アイテム']);

        $this->actingAs($buyer);

        // 小計（購入開始）画面が開ける
        $this->get(route('purchase.show', $item))->assertOk();

        // 支払い方法をコンビニにして開始
        $res = $this->post(route('purchase.submit', $item), [
            'payment_method' => 'konbini',   // purchasesのenumに適合
        ]);
        $res->assertStatus(302);            // 外部遷移(Stripe)へのリダイレクト想定

        // 1) DB側に即時保存している実装なら → purchasesに反映
        $reflected = false;
        if (Schema::hasTable('purchases')) {
            $row = DB::table('purchases')
                ->where('item_id', $item->id)
                ->where('buyer_id', $buyer->id)
                ->orderByDesc('id')->first();
            if ($row) {
                $this->assertEquals('konbini', $row->payment_method);
                $reflected = true;
            }
        }

        // 2) セッションで保持して次画面へ渡す実装なら → セッションに反映
        if (!$reflected) {
            $flat = Arr::dot(session()->all());
            $found = collect($flat)->contains(function ($v, $k) {
                return $v === 'konbini' && str_contains((string)$k, 'payment');
            });
            $this->assertTrue($found, "選択した支払い方法 'konbini' がセッションに反映されていません。");
        }
    }

    /** @test */
    public function カードを選択すると反映される()
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create(['email_verified_at' => now()]);
        $this->ensureProfileCompleted($buyer);
        $item   = $this->makeItemFor($seller, ['name' => 'カード決済アイテム']);

        $this->actingAs($buyer);

        $this->get(route('purchase.show', $item))->assertOk();

        $res = $this->post(route('purchase.submit', $item), [
            'payment_method' => 'card',      // enumに適合
        ]);
        $res->assertStatus(302);

        $reflected = false;
        if (Schema::hasTable('purchases')) {
            $row = DB::table('purchases')
                ->where('item_id', $item->id)
                ->where('buyer_id', $buyer->id)
                ->orderByDesc('id')->first();
            if ($row) {
                $this->assertEquals('card', $row->payment_method);
                $reflected = true;
            }
        }
        if (!$reflected) {
            $flat = \Illuminate\Support\Arr::dot(session()->all());
            $found = collect($flat)->contains(fn($v, $k) => $v === 'card' && str_contains((string)$k, 'payment'));
            $this->assertTrue($found, "選択した支払い方法 'card' がセッションに反映されていません。");
        }
    }
}
