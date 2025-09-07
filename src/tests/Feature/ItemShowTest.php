<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use App\Models\Category;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ItemShowTest extends TestCase
{
    use RefreshDatabase;

    /** itemsテーブルに合わせて安全に1件作る（Factory不使用） */
    private function makeItemFor(User $owner, array $override = []): Item
    {
        $cols = Schema::getColumnListing('items');
        $data = [
            'name'        => '高級ボトル',
            'brand'       => 'ブランドZ',
            'description' => '詳細説明ABC',
            'price'       => 12345,
            'condition'   => '良好',
            'image_path'  => 'https://example.com/image.jpg',
            'seller_id'   => $owner->id,
        ];
        // 存在する列だけに限定
        $data = array_intersect_key($data, array_flip($cols));
        $item = new Item();
        $item->forceFill(array_merge($data, $override))->save();
        return $item->fresh();
    }

    /** commentsテーブルの実列名に合わせてコメントを1件作る */
    private function makeComment(Item $item, User $user, string $text): Comment
    {
        $cols = Schema::getColumnListing('comments');
        // 内容列を自動判定（comment / content / body の順）
        $contentCol = collect(['comment', 'content', 'body'])->first(fn($c) => in_array($c, $cols));

        $c = new Comment();
        $payload = [
            'item_id' => $item->id,
            'user_id' => $user->id,
        ];
        if ($contentCol) {
            $payload[$contentCol] = $text;
        }
        $c->forceFill($payload)->save();
        return $c->fresh();
    }

    /** @test */
    public function 商品詳細に必要な情報が表示される()
    {
        $seller = User::factory()->create();
        $liker1 = User::factory()->create();
        $liker2 = User::factory()->create();
        $cuser1 = User::factory()->create(['name' => 'コメ太郎']);
        $cuser2 = User::factory()->create(['name' => 'レビュー花子']);

        $item = $this->makeItemFor($seller);

        // カテゴリ2件
        $cat1 = Category::query()->firstOrCreate(['name' => '家電']);
        $cat2 = Category::query()->firstOrCreate(['name' => 'スポーツ']);
        $item->categories()->sync([$cat1->id, $cat2->id]);

        // いいね2件
        $item->likedByUsers()->attach([$liker1->id, $liker2->id]);

        // コメント2件
        $this->makeComment($item, $cuser1, 'ナイスな商品です');
        $this->makeComment($item, $cuser2, '欲しいです！');

        // 詳細ページへ
        $res = $this->get(route('items.show', $item));
        $res->assertOk();

        $html = $res->getContent();

        // 画像URL
        $res->assertSee('https://example.com/image.jpg');
        // 基本情報
        $res->assertSee('高級ボトル');     // 商品名
        $res->assertSee('ブランドZ');       // ブランド名
        $res->assertSee('良好');           // 状態
        $res->assertSee('詳細説明ABC');     // 説明
        // 価格（1,2345のどちらでも通す）
        $this->assertTrue(str_contains($html, '12345') || str_contains($html, '12,345'));

        // カテゴリ（複数）
        $res->assertSee('家電');
        $res->assertSee('スポーツ');

        // いいね数・コメント数（改行をまたいでもマッチするように /s を付与）
        $this->assertMatchesRegularExpression('/いいね.*?2/us', $html);
        $this->assertMatchesRegularExpression('/コメント.*?2/us', $html);


        // コメントのユーザー名と内容
        $res->assertSee('コメ太郎');
        $res->assertSee('レビュー花子');
        $res->assertSee('ナイスな商品です');
        $res->assertSee('欲しいです！');
    }

    /** @test */
    public function 複数カテゴリが表示される()
    {
        $seller = User::factory()->create();
        $item   = $this->makeItemFor($seller, ['name' => 'カテゴリ検証アイテム']);

        $cat1 = Category::query()->firstOrCreate(['name' => 'ファッション']);
        $cat2 = Category::query()->firstOrCreate(['name' => 'アクセサリー']);
        $item->categories()->sync([$cat1->id, $cat2->id]);

        $res = $this->get(route('items.show', $item));
        $res->assertOk();
        $res->assertSee('ファッション');
        $res->assertSee('アクセサリー');
    }
}
