<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LikeFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function makeItem(): int
    {
        return DB::table('items')->insertGetId([
            'name' => 'いいね対象',
            'description' => 'desc',
            'price' => 1000,
            'condition' => '良好',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function ゲストはいいね投稿でログインへリダイレクト()
    {
        $itemId = $this->makeItem();
        $this->post("/item/{$itemId}/like")->assertRedirect('/login');
    }

    /** @test */
    public function 認証_プロフィール未完了はプロフィール編集へ誘導()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->be($user);
        $itemId = $this->makeItem();

        $this->post("/item/{$itemId}/like")
            ->assertRedirect('/mypage/profile');
    }

    /** @test */
    public function 認証_プロフィール完了ならトグル成功()
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

        $this->post("/item/{$itemId}/like")->assertStatus(200);
        $this->assertDatabaseHas('likes', ['user_id' => $user->id, 'item_id' => $itemId]);

        $this->post("/item/{$itemId}/like")->assertStatus(200);
        $this->assertDatabaseMissing('likes', ['user_id' => $user->id, 'item_id' => $itemId]);
    }
}
