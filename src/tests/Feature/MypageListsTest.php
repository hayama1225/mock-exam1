<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MypageListsTest extends TestCase
{
    use RefreshDatabase;

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
    public function マイページで出品一覧_購入一覧_いいね一覧に到達できる()
    {
        $this->loginCompleted();
        $this->get('/mypage')->assertOk();
        // 具体的な表示内容はBlade依存のため本テストでは到達確認まで
    }
}
