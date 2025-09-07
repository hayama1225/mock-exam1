<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ログアウトができる()
    {
        $user = User::factory()->create();

        // ログイン状態にする
        $this->actingAs($user);

        // Fortifyの既定 /logout(POST) でログアウト
        $res = $this->post('/logout');

        // 既定のリダイレクトは '/'
        $res->assertRedirect('/');

        // 未ログイン状態になっていること
        $this->assertGuest();
    }
}
