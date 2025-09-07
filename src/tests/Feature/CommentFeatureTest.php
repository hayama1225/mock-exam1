<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class CommentFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.locale' => 'ja']);
        config(['app.fallback_locale' => 'ja']);
    }

    /** itemsテーブルに合わせて安全に1件作る（Factory不使用） */
    private function makeItemFor(User $owner, array $override = []): Item
    {
        $cols = Schema::getColumnListing('items');
        $data = [
            'seller_id'   => $owner->id,
            'name'        => 'コメント対象_' . Str::random(5),
            'brand'       => 'BRAND-Z',
            'description' => 'desc',
            'price'       => 600,
            'condition'   => '良好',
            'image_path'  => 'items/noimage.png',
            'sold_at'     => null,
        ];
        $data = array_intersect_key($data, array_flip($cols));
        $item = new Item();
        $item->forceFill(array_merge($data, $override))->save();
        return $item->fresh();
    }

    /** force.profile 通過用：profiles があれば完了状態を付与（無ければ何もしない） */
    private function ensureProfileCompleted(User $user): void
    {
        if (!Schema::hasTable('profiles')) return;
        $cols = Schema::getColumnListing('profiles');
        $payload = ['user_id' => $user->id];
        if (in_array('profile_completed_at', $cols)) {
            $payload['profile_completed_at'] = now();
        }
        $exists = \DB::table('profiles')->where('user_id', $user->id)->exists();
        $exists
            ? \DB::table('profiles')->where('user_id', $user->id)->update($payload)
            : \DB::table('profiles')->insert($payload);
    }

    /** @test */
    public function ログイン済みのユーザーはコメントを送信できる()
    {
        $seller = User::factory()->create();
        $item   = $this->makeItemFor($seller, ['name' => 'コメントOKアイテム']);
        $user   = User::factory()->create(['email_verified_at' => now()]);
        $this->ensureProfileCompleted($user);

        $this->actingAs($user);
        $res = $this->post(route('items.comments.store', $item), [
            'body' => '最高です！',
        ]);

        // 成功後はリダイレクト（戻り先は実装依存なので 302 のみ確認）
        $res->assertStatus(302);

        // DBに保存されている
        $this->assertDatabaseHas('comments', [
            'item_id' => $item->id,
            'user_id' => $user->id,
            'body'    => '最高です！',
        ]);

        // 詳細ページに表示される
        $show = $this->get(route('items.show', $item));
        $show->assertOk();
        $show->assertSee('最高です！');
    }

    /** @test */
    public function ログイン前のユーザーはコメントを送信できない()
    {
        $seller = User::factory()->create();
        $item   = $this->makeItemFor($seller);

        // 未ログインでPOST → loginへ
        $res = $this->post(route('items.comments.store', $item), [
            'body' => '未ログイン投稿',
        ]);
        $res->assertRedirect(route('login'));

        // 当然保存されていない
        $this->assertDatabaseMissing('comments', [
            'item_id' => $item->id,
            'body'    => '未ログイン投稿',
        ]);

        // 画面には誘導メッセージが出ている
        $page = $this->get(route('items.show', $item));
        $page->assertSee('コメントするには');
        $page->assertSee('ログイン');
    }

    /** @test */
    public function コメント未入力だとバリデーションエラー()
    {
        $seller = User::factory()->create();
        $item   = $this->makeItemFor($seller);
        $user   = User::factory()->create(['email_verified_at' => now()]);
        $this->ensureProfileCompleted($user);

        $this->actingAs($user);
        $res = $this->from(route('items.show', $item))
            ->post(route('items.comments.store', $item), [
                'body' => '',
            ]);

        $res->assertRedirect(route('items.show', $item));
        $res->assertSessionHasErrors([
            'body' => '商品コメントを入力してください',
        ]);
    }

    /** @test */
    public function コメントが255字以上だとバリデーションエラー()
    {
        $seller = User::factory()->create();
        $item   = $this->makeItemFor($seller);
        $user   = User::factory()->create(['email_verified_at' => now()]);
        $this->ensureProfileCompleted($user);

        $this->actingAs($user);
        $long = str_repeat('あ', 256); // 256文字でアウト
        $res = $this->from(route('items.show', $item))
            ->post(route('items.comments.store', $item), [
                'body' => $long,
            ]);

        $res->assertRedirect(route('items.show', $item));
        $res->assertSessionHasErrors([
            'body' => '商品コメントは255文字以内で入力してください',
        ]);
    }
}
