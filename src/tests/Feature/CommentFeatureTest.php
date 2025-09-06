<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommentFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function makeItem(): int
    {
        return DB::table('items')->insertGetId([
            'name' => 'コメント対象',
            'description' => 'desc',
            'price' => 1000,
            'condition' => '良好',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function ゲストはコメント投稿でログインへ()
    {
        $itemId = $this->makeItem();
        $this->post("/item/{$itemId}/comments", [])->assertRedirect('/login');
    }

    /** @test */
    public function 認証_未完了はプロフィール編集へ()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->be($user);
        $itemId = $this->makeItem();

        $this->post("/item/{$itemId}/comments", ['body' => 'test'])
            ->assertRedirect('/mypage/profile');
    }

    /** @test */
    public function バリデーション_本文必須と255文字以内()
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
        $itemId = $this->makeItem();

        // 未入力
        $this->post("/item/{$itemId}/comments", ['body' => ''])
            ->assertSessionHasErrors(['body']);

        // 256文字
        $this->post("/item/{$itemId}/comments", ['body' => str_repeat('あ', 256)])
            ->assertSessionHasErrors(['body']);

        // 正常
        $this->post("/item/{$itemId}/comments", ['body' => 'OK'])
            ->assertStatus(302); // 保存後にリダイレクト想定
        $this->assertDatabaseHas('comments', ['item_id' => $itemId, 'body' => 'OK']);
    }
}
