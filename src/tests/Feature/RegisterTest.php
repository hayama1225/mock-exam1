<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.locale' => 'ja']);
        config(['app.fallback_locale' => 'ja']);
    }

    // --- Helpers -------------------------------------------------------------

    /** Fortifyでメール認証を使うなら verify 画面、そうでなければ /mypage/profile を期待 */
    private function expectedRegisterRedirect(): string
    {
        // Fortifyが提供するメール認証通知ルート
        if (RouteFacade::has('verification.notice')) {
            return route('verification.notice');
        }
        return '/mypage/profile';
    }

    // --- Specs ---------------------------------------------------------------

    /** @test */
    public function 名前が未入力だとエラー()
    {
        $res = $this->from('/register')->post('/register', [
            'name' => '',
            'email' => 'taro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $res->assertRedirect('/register');
        $res->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }

    /** @test */
    public function メールが未入力だとエラー()
    {
        $res = $this->from('/register')->post('/register', [
            'name' => '太郎',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $res->assertRedirect('/register');
        $res->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /** @test */
    public function パスワードが未入力だとエラー()
    {
        $res = $this->from('/register')->post('/register', [
            'name' => '太郎',
            'email' => 'taro@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $res->assertRedirect('/register');
        $res->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /** @test */
    public function パスワードが8文字未満だとエラー()
    {
        $res = $this->from('/register')->post('/register', [
            'name' => '太郎',
            'email' => 'taro@example.com',
            'password' => 'pass12', // 6文字
            'password_confirmation' => 'pass12',
        ]);

        $res->assertRedirect('/register');
        $res->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    /** @test */
    public function 確認用パスワードが未入力だとエラー()
    {
        $res = $this->from('/register')->post('/register', [
            'name' => '太郎',
            'email' => 'taro@example.com',
            'password' => 'password123',
            'password_confirmation' => '',
        ]);

        $res->assertRedirect('/register');
        $res->assertSessionHasErrors([
            'password_confirmation' => '確認用パスワードを入力してください',
        ]);
    }

    /** @test */
    public function 確認用パスワードが一致しないとエラー()
    {
        $res = $this->from('/register')->post('/register', [
            'name' => '太郎',
            'email' => 'taro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password124',
        ]);

        $res->assertRedirect('/register');
        $res->assertSessionHasErrors([
            // RegisterRequest の messages に合わせる（「と」）
            'password_confirmation' => 'パスワードと一致しません',
        ]);
    }

    /** @test */
    public function 正しく入力すればユーザー作成され期待の画面へ遷移する()
    {
        $res = $this->post('/register', [
            'name' => '太郎',
            'email' => 'taro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Fortify(メール認証あり)なら verify 通知、なければ /mypage/profile
        $res->assertRedirect($this->expectedRegisterRedirect());

        $this->assertDatabaseHas('users', [
            'email' => 'taro@example.com',
            'name'  => '太郎',
        ]);
    }
}
