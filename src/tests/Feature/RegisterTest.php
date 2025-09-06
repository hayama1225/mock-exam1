<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 名前が未入力ならバリデーションエラーになる()
    {
        $res = $this->post('/register', [
            'name' => '',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $res->assertSessionHasErrors(['name']);
    }

    /** @test */
    public function メールアドレスが未入力ならバリデーションエラーになる()
    {
        $res = $this->post('/register', [
            'name' => '太郎',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $res->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function パスワードが未入力ならバリデーションエラーになる()
    {
        $res = $this->post('/register', [
            'name' => '太郎',
            'email' => 'user@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);
        $res->assertSessionHasErrors(['password']);
    }

    /** @test */
    public function パスワードが8文字未満ならバリデーションエラーになる()
    {
        $res = $this->post('/register', [
            'name' => '太郎',
            'email' => 'user@example.com',
            'password' => 'short7',
            'password_confirmation' => 'short7',
        ]);
        $res->assertSessionHasErrors(['password']);
    }

    /** @test */
    public function パスワード確認が一致しなければバリデーションエラーになる()
    {
        $res = $this->post('/register', [
            'name' => '太郎',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'DIFFERENT',
        ]);
        // ← ここを password_confirmation に修正
        $res->assertSessionHasErrors(['password_confirmation']);
    }

    /** @test */
    public function 正常登録後はメール認証画面へリダイレクトされる()
    {
        $res = $this->post('/register', [
            'name' => '太郎',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $res->assertRedirect('/email/verify');
        $this->assertDatabaseHas('users', [
            'email' => 'user@example.com',
            'name'  => '太郎',
        ]);
        $this->assertAuthenticated();
    }
}
