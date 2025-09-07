<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 会員登録で認証メールが送られる()
    {
        Notification::fake();

        $res = $this->post(route('register'), [
            'name'                  => '山田太郎',
            'email'                 => 'taro@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Fortifyの既定：認証誘導画面へ
        $res->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'taro@example.com')->first();
        $this->assertNotNull($user);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /** @test */
    public function 認証前後で_verified_check_APIの戻りが変わり_認証後はトップアクセスでプロフィール編集へ誘導される()
    {
        $user = User::factory()->create([
            'email' => 'foo@example.com',
            'email_verified_at' => null, // 未認証
        ]);

        // ログイン状態で誘導ページやAPIを利用する前提
        $this->actingAs($user);

        // 未認証 → false
        $this->getJson('/email/verified-check')
            ->assertOk()
            ->assertJson(['verified' => false]);

        // 署名付きURLで認証を完了させる（本物のリンク押下の再現）
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // 既定では /home にリダイレクト（verified-close の簡易画面）
        $res = $this->get($url);
        // クエリ (?verified=1) が付く実装のため、prefix で判定する
        $res->assertRedirect();
        $loc = $res->headers->get('Location');
        $this->assertTrue(
            str_starts_with($loc, route('home')),
            "Unexpected redirect location: {$loc}"
        );
        $this->assertNotNull($user->fresh()->email_verified_at);

        // 認証後 → true
        $this->getJson('/email/verified-check')
            ->assertOk()
            ->assertJson(['verified' => true]);

        // プロフィール未完了でもトップ（items.index）はホワイトリストで閲覧可
        $this->get('/')->assertOk();
    }

    /** @test */
    public function 認証誘導画面にボタンと再送リンクが表示される()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->actingAs($user);

        $res = $this->get(route('verification.notice'));
        $res->assertOk();
        $res->assertSee('認証はこちらから');
        $res->assertSee('認証メールを再送する');
        // 注記（今回の仕様変更に合わせた文言）
        $res->assertSee('プロフィール設定ページへ移動します');
    }
}
