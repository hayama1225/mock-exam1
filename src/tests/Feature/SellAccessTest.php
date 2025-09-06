<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SellAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ゲストは出品画面にアクセスできずログインへ()
    {
        $res = $this->get('/sell');
        $res->assertRedirect('/login');
    }

    /** @test */
    public function 認証済みでもプロフィール未完了ならプロフィール編集へ誘導()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->be($user);

        $res = $this->get('/sell');
        $res->assertRedirect('/mypage/profile');
    }

    /** @test */
    public function プロフィール完了なら出品画面200()
    {
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

        $res = $this->get('/sell');
        $res->assertOk();
    }
}
