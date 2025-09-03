<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sig     = $request->header('Stripe-Signature');
        $secret  = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig, $secret);
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook signature invalid', ['err' => $e->getMessage()]);
            return response('Invalid', 400);
        }

        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        switch ($event->type) {
            case 'checkout.session.completed': {
                    $session = $event->data->object; // \Stripe\Checkout\Session
                    Log::info('checkout.session.completed', [
                        'session'        => $session->id,
                        'payment_intent' => $session->payment_intent,
                        'pay_method'     => $session->metadata->pay_method ?? null,
                        'item_id'        => $session->metadata->item_id ?? null,
                    ]);
                    break;
                }

                // ★カード/コンビニとも最終確定で飛ぶ
            case 'payment_intent.succeeded': {
                    $pi   = $event->data->object; // \Stripe\PaymentIntent
                    $piId = $pi->id;

                    // metadata を取得（無ければ Session 逆引き）
                    $meta = $pi->metadata ?? [];
                    if (!isset($meta['item_id'])) {
                        try {
                            $sessions = \Stripe\Checkout\Session::all([
                                'payment_intent' => $piId,
                                'limit'          => 1,
                            ]);
                            if (!empty($sessions->data)) {
                                $meta = $sessions->data[0]->metadata ?? [];
                            }
                        } catch (\Throwable $e) {
                            Log::warning('Failed to fetch session by PI', ['pi' => $piId, 'err' => $e->getMessage()]);
                        }
                    }

                    $this->finalizeFromMeta($meta, 'paid');
                    break;
                }

                // ★非同期支払い（コンビニ等）で飛ぶことがある → 同じく確定処理
            case 'checkout.session.async_payment_succeeded': {
                    $session = $event->data->object; // \Stripe\Checkout\Session

                    // まず Session の metadata を使う
                    $meta = (array)($session->metadata ?? []);
                    // 不足があれば PI から補完
                    if (!isset($meta['item_id']) || !$meta['item_id']) {
                        try {
                            $pi = \Stripe\PaymentIntent::retrieve($session->payment_intent);
                            $piMeta = (array)($pi->metadata ?? []);
                            $meta = array_merge($piMeta, $meta);
                        } catch (\Throwable $e) {
                            Log::warning('Failed to fetch PI for async_session', ['session' => $session->id, 'err' => $e->getMessage()]);
                        }
                    }

                    $this->finalizeFromMeta($meta, 'paid');
                    break;
                }
        }

        return response('OK', 200);
    }

    /**
     * metadata から Item/Purchase を確定更新
     */
    private function finalizeFromMeta(array $meta, string $status): void
    {
        $itemId    = isset($meta['item_id'])  ? (int)$meta['item_id']  : 0;
        $buyerId   = isset($meta['buyer_id']) ? (int)$meta['buyer_id'] : null;
        $payMethod = $meta['pay_method'] ?? null;

        $shipZip  = $meta['ship_zip']  ?? null;
        $shipAddr = $meta['ship_addr'] ?? null;
        $shipBldg = $meta['ship_bldg'] ?? null;
        $qty      = (int)($meta['quantity'] ?? 1);

        if ($itemId <= 0) {
            Log::warning('finalizeFromMeta: no item_id', compact('meta'));
            return;
        }

        $item = Item::find($itemId);
        if (!$item) {
            Log::warning('finalizeFromMeta: item not found', compact('itemId'));
            return;
        }

        // items を SOLD に
        if ($buyerId && Schema::hasColumn('items', 'buyer_id')) $item->buyer_id = $buyerId;
        if (Schema::hasColumn('items', 'sold_at'))              $item->sold_at  = now();
        if (Schema::hasColumn('items', 'is_sold'))              $item->is_sold  = true;
        $item->save();

        // purchases を住所込みで Upsert（テーブルに合わせて安全に）
        $this->upsertPurchaseWithAddress(
            $item,
            $buyerId,
            $status,
            $shipZip,
            $shipAddr,
            $shipBldg,
            $qty,
            $payMethod
        );

        Log::info('finalized purchase/item', ['item_id' => $itemId, 'status' => $status, 'pay_method' => $payMethod]);
    }

    private function upsertPurchaseWithAddress(
        Item $item,
        ?int $buyerId,
        string $status,
        ?string $zip,
        ?string $address,
        ?string $building,
        int $quantity = 1,
        ?string $payMethod = null
    ): void {
        if (!class_exists(\App\Models\Purchase::class)) return;

        $needZip     = Schema::hasColumn('purchases', 'zip');
        $needAddress = Schema::hasColumn('purchases', 'address');
        if (($needZip && !$zip) || ($needAddress && !$address)) {
            Log::info('Skip purchases insert (address required but missing) [webhook]');
            return;
        }

        $vals = ['status' => $status];

        if (Schema::hasColumn('purchases', 'amount')) {
            $vals['amount'] = (int)$item->price;
        } elseif (Schema::hasColumn('purchases', 'price')) {
            $vals['price'] = (int)$item->price;
        }

        if (Schema::hasColumn('purchases', 'zip'))      $vals['zip']      = $zip ?? '';
        if (Schema::hasColumn('purchases', 'address'))  $vals['address']  = $address ?? '';
        if (Schema::hasColumn('purchases', 'building')) $vals['building'] = $building ?? '';

        if (Schema::hasColumn('purchases', 'quantity'))   $vals['quantity']   = $quantity;
        if (Schema::hasColumn('purchases', 'pay_method')) $vals['pay_method'] = $payMethod ?? '';
        if ($status === 'paid' && Schema::hasColumn('purchases', 'paid_at')) {
            $vals['paid_at'] = now();
        }

        try {
            \App\Models\Purchase::updateOrCreate(
                ['item_id' => $item->id, 'buyer_id' => $buyerId],
                $vals
            );
        } catch (\Throwable $e) {
            Log::warning('Purchase upsert failed but skipped [webhook]', ['err' => $e->getMessage()]);
        }
    }
}
