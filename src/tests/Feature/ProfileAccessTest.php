<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ゲストはプロフィール編集にアクセスできずログインへ()
    {
        $res = $this->get('/mypage/profile');
        $res->assertRedirect('/login');
    }

    /** @test */
    public function 認証済み未認証ユーザーはメール認証へ()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->be($user);

        $res = $this->get('/mypage/profile');
        $res->assertRedirect('/email/verify');
    }

    /** @test */
    public function 認証済みならプロフィール編集200_未完了でも入れる()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->be($user);

        $res = $this->get('/mypage/profile');
        $res->assertOk();
    }
}

