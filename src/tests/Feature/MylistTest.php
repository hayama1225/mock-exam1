<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MylistTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 未ログインでマイリストタブは200()
    {
        $res = $this->get('/?tab=mylist');
        $res->assertOk();
    }

    /** @test */
    public function ログイン_プロフィール未完了はプロフィール編集へ誘導()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->be($user);
        $this->get('/?tab=mylist')->assertRedirect('/mypage/profile');
    }

    /** @test */
    public function ログイン_プロフィール完了は200()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        DB::table('profiles')->insert([
            'user_id' => $user->id,
            'zip' => '100-0001',
            'address' => '東京都千代田区1-1',
            'building' => null,
            'profile_completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->be($user);
        $this->get('/?tab=mylist')->assertOk();
    }
}
