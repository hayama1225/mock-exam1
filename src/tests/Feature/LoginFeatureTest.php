<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.locale' => 'ja']);
        config(['app.fallback_locale' => 'ja']);
    }

    /** ログイン成功時の実装どおりの遷移先を算出 */
    private function expectedAfterLogin(User $user): string
    {
        if ($user instanceof MustVerifyEmail && ! is_null($user->email_verified_at)) {
            // プロフィール未完了なら /mypage/profile
            $p = $user->profile; // リレーションが未作成でも null 安全
            if (! $p || is_null($p->profile_completed_at)) {
                return route('profile.edit'); // /mypage/profile
            }
        }
        // それ以外は intended(RouteServiceProvider::HOME) = '/'
        return \App\Providers\RouteServiceProvider::HOME;
    }

    /** @test */
    public function メールアドレス未入力でエラー()
    {
        $res = $this->from('/login')->post('/login', [
            'email'    => '',
            'password' => 'password',
        ]);

        $res->assertRedirect('/login');
        // 翻訳に追従（スペース有無・属性名も翻訳）
        $res->assertSessionHasErrors([
            'email' => trans('validation.required', [
                'attribute' => trans('validation.attributes.email'),
            ]),
        ]);
    }

    /** @test */
    public function パスワード未入力でエラー()
    {
        $res = $this->from('/login')->post('/login', [
            'email'    => 'taro@example.com',
            'password' => '',
        ]);

        $res->assertRedirect('/login');
        $res->assertSessionHasErrors([
            'password' => trans('validation.required', [
                'attribute' => trans('validation.attributes.password'),
            ]),
        ]);
    }

    /** @test */
    public function 入力情報が間違っていると失敗メッセージ()
    {
        $res = $this->from('/login')->post('/login', [
            'email'    => 'no-user@example.com',
            'password' => 'wrong',
        ]);

        $res->assertRedirect('/login');
        // auth.failed をそのまま参照（プロジェクトの日本語に追従）
        $res->assertSessionHasErrors([
            'email' => trans('auth.failed'),
        ]);
    }

    /** @test */
    public function 正しい情報ならログイン処理が実行される()
    {
        // factory 既定パスワードは 'password'
        $user = User::factory()->create([
            'email' => 'taro@example.com',
            'email_verified_at' => now(), // 認証済みとしてログイン
        ]);

        $res = $this->post('/login', [
            'email'    => 'taro@example.com',
            'password' => 'password',
        ]);

        // 実装通り：認証済み & プロフ未完了 → /mypage/profile、そうでなければ '/'
        $res->assertRedirect($this->expectedAfterLogin($user));
        $this->assertAuthenticatedAs($user);
    }
}
