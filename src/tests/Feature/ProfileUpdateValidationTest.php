<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateValidationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 認証必須_未認証はログインへ()
    {
        $this->post('/mypage/profile', [])->assertRedirect('/login');
    }

    /** @test */
    public function 認証済み_未認証メールはメール認証へ()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->be($user);
        $this->post('/mypage/profile', [])->assertRedirect('/email/verify');
    }

    /** @test */
    public function バリデーション_必須と形式が効く()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->be($user);

        // すべて未入力
        $this->post('/mypage/profile', [
            'avatar' => null,
            'username' => '',
            'zip' => '',
            'address' => ''
        ])->assertSessionHasErrors(['username', 'zip', 'address']);

        // 形式違反
        $this->post('/mypage/profile', [
            'username' => str_repeat('a', 21),
            'zip' => '1234567',
            'address' => '',
        ])->assertSessionHasErrors(['username', 'zip', 'address']);

        // 正常
        $this->post('/mypage/profile', [
            'username' => '太郎',
            'zip' => '123-4567',
            'address' => '東京都',
        ])->assertStatus(302); // 成功後リダイレクト想定
    }
}
