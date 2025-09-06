<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchaseSubmitValidationTest extends TestCase
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
    public function バリデーション_支払い方法と配送先は必須()
    {
        $itemId = $this->makeItem();
        $this->loginCompleted();

        $this->post("/purchase/{$itemId}", [
            'payment_method' => '',
            'shipping_source' => '',
        ])->assertSessionHasErrors(['payment_method', 'shipping_source']);

        $this->post("/purchase/{$itemId}", [
            'payment_method' => 'card',
            'shipping_source' => 'profile',
        ])->assertStatus(302);
    }
}
