<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'item_id'    => ['required', 'integer', 'exists:items,id'],
            'quantity'   => ['nullable', 'integer', 'min:1'],
            'pay_method' => ['required', 'in:card,konbini'],
        ]);

        $item     = Item::findOrFail($request->item_id);
        $quantity = (int)($request->quantity ?? 1);

        // 画像URL（外部URL or storage配下をpublic URL化）
        $imageUrl = null;
        if ($item->image_path) {
            $imageUrl = Str::startsWith($item->image_path, ['http://', 'https://'])
                ? $item->image_path
                : asset('storage/' . $item->image_path);
        }

        // Stripe 初期化
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        // 支払い方法の出し分け
        $paymentMethodTypes = $request->pay_method === 'konbini'
            ? ['konbini']  // コンビニ（JPY必須）
            : ['card'];    // カード

        // Checkout セッション作成
        $session = \Stripe\Checkout\Session::create([
            'mode'                  => 'payment',
            'payment_method_types'  => $paymentMethodTypes,
            'line_items' => [[
                'price_data' => [
                    'currency'    => 'jpy',
                    'unit_amount' => (int) $item->price, // 税込・円
                    'product_data' => [
                        'name'   => $item->name,
                        'images' => $imageUrl ? [$imageUrl] : [],
                    ],
                ],
                'quantity' => $quantity,
            ]],
            'locale'      => 'ja',
            'success_url' => route('checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('checkout.cancel'),
            'metadata'    => [
                'item_id'    => (string)$item->id,
                'quantity'   => (string)$quantity,
                'pay_method' => $request->pay_method,
            ],
        ]);

        return redirect()->away($session->url);
    }

    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');
        if (!$sessionId) {
            return redirect('/mypage?tab=buy')->with('status', '決済結果の確認に失敗しました。');
        }

        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        $session = \Stripe\Checkout\Session::retrieve($sessionId);
        $status  = $session->payment_status; // 'paid' | 'unpaid' | 'no_payment_required'

        // TODO: 決済確定処理（DB更新）をここで行う
        // 例) if ($status === 'paid') { Order::update(...); }

        return redirect('/mypage?tab=buy')
            ->with('status', $status === 'paid'
                ? '決済が完了しました。ありがとうございました。'
                : 'お支払い手続きを受け付けました。コンビニでのお支払い完了後に反映されます。');
    }

    public function cancel()
    {
        return redirect('/mypage?tab=buy')->with('status', '決済をキャンセルしました。');
    }
}
