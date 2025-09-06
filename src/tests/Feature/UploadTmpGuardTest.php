<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UploadTmpGuardTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ゲストは一時アップロードにアクセスできずログインへ()
    {
        $res = $this->post('/upload/tmp', []);
        $res->assertRedirect('/login');
    }

    /** @test */
    public function 認証済みでもプロフィール未完了ならプロフィール編集へ誘導()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->be($user);

        $res = $this->post('/upload/tmp', []);
        $res->assertRedirect('/mypage/profile');
    }
}
