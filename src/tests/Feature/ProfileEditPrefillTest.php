<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProfileEditPrefillTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function プロフィール編集画面に過去設定が初期値として表示される()
    {
        // 1) ユーザー（メール認証済）を用意
        $user = User::factory()->create([
            'name'              => 'ユーザー太郎',
            'email'             => 'taro@example.com',
            'email_verified_at' => now(),
        ]);

        // 2) profiles に過去設定を投入
        //    （ビューでは asset('storage/'.$avatar_path) になる）
        $this->assertTrue(Schema::hasTable('profiles'), 'profiles テーブルがありません');
        DB::table('profiles')->insert([
            'user_id'               => $user->id,
            'username'              => 'seed_seller',
            'zip'                   => '123-4567',
            'address'               => '東京都渋谷区千駄ヶ谷1-2-3',
            'building'              => '千駄ヶ谷マンション 101',
            'avatar_path'           => 'avatars/taro.png',
            'profile_completed_at'  => now(),
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        // 3) ログインしてプロフィール編集画面へ
        $this->actingAs($user);
        $res = $this->get(route('profile.edit'));
        $res->assertOk();

        // 4) 初期値がプレフィルされていること
        //    画像は storage/avatars/taro.png に解決される想定
        $res->assertSee('storage/avatars/taro.png');

        // input の value をチェック（HTMLエスケープ抑止のため第2引数 false）
        $res->assertSee('value="seed_seller"', false);              // ユーザー名
        $res->assertSee('value="123-4567"', false);                 // 郵便番号
        $res->assertSee('value="東京都渋谷区千駄ヶ谷1-2-3"', false); // 住所
        $res->assertSee('value="千駄ヶ谷マンション 101"', false);      // 建物名
    }
}
