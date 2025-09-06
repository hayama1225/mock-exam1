<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchaseAddressUpdateValidationTest extends TestCase
{
    use RefreshDatabase;

    private function makeItem(): int
    {
        return DB::table('items')->insertGetId([
            'name' => '購入対象',
            'description' => 'desc',
            'price' => 1200,
            'condition' => '良好',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function loginCompleted(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        DB::table('profiles')->insert([
            'user_id' => $user->id,
            'zip' => '100-0001',
            'address' => '東京都',
            'profile_completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->be($user);
        return $user;
    }

    /** @test */
    public function バリデーション_郵便番号と住所は必須_形式()
    {
        $itemId = $this->makeItem();
        $this->loginCompleted();

        // 未入力
        $this->post("/purchase/address/{$itemId}", [
            'zip' => '',
            'address' => '',
            'building' => ''
        ])->assertSessionHasErrors(['zip', 'address']);

        // 形式違反
        $this->post("/purchase/address/{$itemId}", [
            'zip' => '1234567',
            'address' => ''
        ])->assertSessionHasErrors(['zip', 'address']);

        // 正常
        $this->post("/purchase/address/{$itemId}", [
            'zip' => '123-4567',
            'address' => '東京都',
            'building' => 'ABC'
        ])->assertStatus(302);
    }
}
