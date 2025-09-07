<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ItemCreateStoreTest extends TestCase
{
    use RefreshDatabase;

    /** プロフィール完了フラグを立てて ForceProfileSetup を通す */
    private function completeProfile(User $user): void
    {
        if (!Schema::hasTable('profiles')) return;

        DB::table('profiles')->updateOrInsert(
            ['user_id' => $user->id],
            ['profile_completed_at' => now(), 'created_at' => now(), 'updated_at' => now()]
        );
    }

    /** @test */
    public function 出品画面から必須情報を保存できる()
    {
        // 出品者（メール認証済＋プロフィール完了）
        $seller = User::factory()->create(['email_verified_at' => now()]);
        $this->completeProfile($seller);
        $this->actingAs($seller);

        // カテゴリを2件用意
        $cat1 = Category::create(['name' => 'レディース']);
        $cat2 = Category::create(['name' => 'キッチン']);

        // 画像の保存先をフェイク & 一時ファイルを事前配置
        Storage::fake('public');
        Storage::disk('public')->put('tmp/dummy.jpg', 'dummy-bytes');

        // POST（image の代わりに image_tmp を送る）
        $payload = [
            'categories'  => [$cat1->id, $cat2->id],
            'condition'   => '良好',
            'name'        => 'テスト出品',
            'brand'       => 'BRAND-X',
            'description' => '説明ABC',
            'price'       => '1200',
            'image_tmp'   => 'tmp/dummy.jpg',
        ];

        $res = $this->post(route('items.store'), $payload);
        $res->assertStatus(302); // 成功後リダイレクト

        // 直近で作られたアイテムを取得
        $item = Item::query()->latest('id')->first();
        $this->assertNotNull($item);

        // 保存内容の確認
        $this->assertSame('テスト出品', $item->name);
        $this->assertSame('BRAND-X', $item->brand);
        $this->assertSame('説明ABC', $item->description);
        $this->assertSame(1200, (int)$item->price);

        // 画像は tmp から items/xxx へ移動されている
        $this->assertNotEmpty($item->image_path);
        $this->assertTrue(str_starts_with($item->image_path, 'items/'));
        $this->assertTrue(Storage::disk('public')->exists($item->image_path));
        $this->assertFalse(Storage::disk('public')->exists('tmp/dummy.jpg'));

        // カテゴリの紐付け
        $this->assertEqualsCanonicalizing(
            [$cat1->id, $cat2->id],
            $item->categories()->pluck('categories.id')->all()
        );
    }
}
