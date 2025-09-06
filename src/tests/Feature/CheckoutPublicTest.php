<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutPublicTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function チェックアウト成功ページは公開で到達時にリダイレクトでも良い()
    {
        $res = $this->get('/checkout/success');
        $res->assertStatus(302);
    }

    /** @test */
    public function チェックアウトキャンセルページは公開で到達時にリダイレクトでも良い()
    {
        $res = $this->get('/checkout/cancel');
        $res->assertStatus(302);
    }
}
