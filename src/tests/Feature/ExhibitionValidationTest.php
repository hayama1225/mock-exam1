<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExhibitionValidationTest extends TestCase
{
    use RefreshDatabase;

    private function loginWithCompletedProfile(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        DB::table('profiles')->insert([
            'user_id' => $user->id,
            'zip' => '100-0001',
            'address' => '東京都',
            'profile_completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->be($user);
        return $user;
    }

    /** @test */
    public function ガード_ゲストはログインへ_未完了はプロフィールへ()
    {
        $this->post('/sell', [])->assertRedirect('/login');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->be($user);
        $this->post('/sell', [])->assertRedirect('/mypage/profile');
    }

    /** @test */
    public function バリデーション_カテゴリ必須_価格数値_説明255以内_画像必須ロジック()
    {
        $this->loginWithCompletedProfile();

        // 何も入れない（画像は image or image_tmp のどちらか必須）
        $this->post('/sell', [
            'categories' => [],
            'condition' => '',
            'name' => '',
            'brand' => '',
            'description' => str_repeat('あ', 256),
            'price' => 'abc',
            'image' => null,
            'image_tmp' => null,
        ])->assertSessionHasErrors(['categories', 'condition', 'name', 'description', 'price', 'image']);

        // 正常（image_tmp を指定して画像要件を満たす）
        $this->post('/sell', [
            'categories' => [1],
            'condition' => '良好',
            'name' => '商品',
            'brand' => 'B',
            'description' => '説明',
            'price' => 1000,
            'image_tmp' => 'tmp/xxx.png',
        ])->assertStatus(302);
    }
}
