<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutCreateGuardTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ゲストはチェックアウト作成にアクセスできずログインへ()
    {
        $res = $this->post('/checkout', []);
        $res->assertRedirect('/login');
    }

    /** @test */
    public function 認証済みでもプロフィール未完了ならプロフィール編集へ誘導()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->be($user);

        $res = $this->post('/checkout', []);
        $res->assertRedirect('/mypage/profile');
    }
}

