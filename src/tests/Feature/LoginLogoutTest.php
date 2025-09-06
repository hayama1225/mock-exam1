<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginLogoutTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ログイン_メール未入力でエラー()
    {
        $res = $this->post('/login', ['email' => '', 'password' => 'password123']);
        $res->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /** @test */
    public function ログイン_パスワード未入力でエラー()
    {
        $res = $this->post('/login', ['email' => 'user@example.com', 'password' => '']);
        $res->assertSessionHasErrors(['password']);
        $this->assertGuest();
    }

    /** @test */
    public function ログイン_誤った資格情報でエラー()
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('correct-pass'),
            'email_verified_at' => now(),
        ]);

        $res = $this->post('/login', ['email' => 'user@example.com', 'password' => 'wrong-pass']);
        $res->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /** @test */
    public function ログイン_成功でプロフィール設定へリダイレクト()
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $res = $this->post('/login', ['email' => 'user@example.com', 'password' => 'password123']);

        // 実測：/mypage/profile へ
        $res->assertRedirect('/mypage/profile');
        $this->assertAuthenticated();
    }

    /** @test */
    public function ログアウトで未ログイン状態になりトップへ戻る想定()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->be($user);

        $res = $this->post('/logout');
        $res->assertRedirect('/');
        $this->assertGuest();
    }
}
