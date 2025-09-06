<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchasePagesTest extends TestCase
{
    use RefreshDatabase;

    private function makeItemId(): int
    {
        return DB::table('items')->insertGetId([
            'name' => '購入テスト商品',
            'description' => '説明',
            'price' => 1200,
            'condition' => '良好',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function ゲストは購入画面にアクセスできずログインへ()
    {
        $itemId = $this->makeItemId();
        $res = $this->get('/purchase/' . $itemId);
        $res->assertRedirect('/login');
    }

    /** @test */
    public function プロフィール未完了はプロフィール編集へ誘導()
    {
        $itemId = $this->makeItemId();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->be($user);

        $res = $this->get('/purchase/' . $itemId);
        $res->assertRedirect('/mypage/profile');
    }

    /** @test */
    public function プロフィール完了なら購入画面200()
    {
        $itemId = $this->makeItemId();
        $user = User::factory()->create(['email_verified_at' => now()]);

        DB::table('profiles')->insert([
            'user_id' => $user->id,
            'zip' => '100-0001',
            'address' => '東京都千代田区1-1',
            'building' => null,
            'profile_completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->be($user);

        $res = $this->get('/purchase/' . $itemId);
        $res->assertOk();
    }
}
