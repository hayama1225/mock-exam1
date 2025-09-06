<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerifiedCheckTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ゲストは認証チェックAPIにアクセスできずログインへ()
    {
        $res = $this->get('/email/verified-check');
        $res->assertRedirect('/login');
    }

    /** @test */
    public function 認証済み未認証ユーザーはfalseが返る()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->be($user);

        $res = $this->get('/email/verified-check');
        $res->assertOk()
            ->assertJson(['verified' => false]);
    }

    /** @test */
    public function 認証済み認証済ユーザーはtrueが返る()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->be($user);

        $res = $this->get('/email/verified-check');
        $res->assertOk()
            ->assertJson(['verified' => true]);
    }
}

