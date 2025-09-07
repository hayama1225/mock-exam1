<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Profile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'item_id'         => ['required', 'integer', 'exists:items,id'],
            'quantity'        => ['nullable', 'integer', 'min:1'],
            'pay_method'      => ['required', 'in:card,konbini'],
            'shipping_source' => ['nullable', 'in:profile,custom'],
        ]);

        $item     = Item::findOrFail($request->item_id);
        $quantity = (int)($request->quantity ?? 1);

        $imageUrl = null;
        if ($item->image_path) {
            $imageUrl = Str::startsWith($item->image_path, ['http://', 'https://'])
                ? $item->image_path
                : asset('storage/' . $item->image_path);
        }

        // ★ 住所を取得（プロフィール or セッション）
        [$shipZip, $shipAddr, $shipBldg, $shipSource] = $this->resolveShippingAddress($request); // FIX: セッションキー統一

        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        $paymentMethodTypes = $request->pay_method === 'konbini' ? ['konbini'] : ['card'];

        try {
            $session = \Stripe\Checkout\Session::create([
                'mode'                 => 'payment',
                'payment_method_types' => $paymentMethodTypes,
                'line_items' => [[
                    'price_data' => [
                        'currency'    => 'jpy',
                        'unit_amount' => (int) $item->price,
                        'product_data' => [
                            'name'   => $item->name,
                            'images' => $imageUrl ? [$imageUrl] : [],
                        ],
                    ],
                    'quantity' => $quantity,
                ]],
                'locale'         => 'ja',
                'customer_email' => auth()->check() ? auth()->user()->email : null,
                'success_url'    => route('checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'     => route('checkout.cancel'),
                // ★ success/webhook で復元
                'metadata' => [
                    'item_id'     => (string)$item->id,
                    'quantity'    => (string)$quantity,
                    'pay_method'  => $request->pay_method,
                    'buyer_id'    => auth()->check() ? (string)auth()->id() : '',
                    'ship_zip'    => (string)($shipZip ?? ''),
                    'ship_addr'   => (string)($shipAddr ?? ''),
                    'ship_bldg'   => (string)($shipBldg ?? ''),
                    'ship_source' => (string)$shipSource,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Stripe Checkout create failed', ['error' => $e->getMessage()]);
            return back()->withErrors([
                'pay_method' => '決済の初期化に失敗しました。時間をおいて再度お試しください。',
            ]);
        }

        // コンビニは pending を事前作成
        if ($request->pay_method === 'konbini') {
            $this->upsertPurchaseWithAddress(
                $item,
                auth()->id(),
                'pending',
                $shipZip,
                $shipAddr,
                $shipBldg,
                $quantity,
                'konbini',
            );

            return view('purchases.konbini_ready', [
                'sessionUrl' => $session->url,
                'item'       => $item,
            ]);
        }

        // カードは即リダイレクト
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

        $meta = $session->metadata ?? (object)[];
        $cd = $session->customer_details ?? null;
        $ad = $cd && isset($cd->address) ? $cd->address : null;

        if ($ad) {
            if (empty($meta->ship_zip))  $meta->ship_zip  = $ad->postal_code ?? null;
            if (empty($meta->ship_addr)) $meta->ship_addr = trim(implode(' ', array_filter([
                $ad->state ?? null,
                $ad->city ?? null,
                $ad->line1 ?? null,
            ]))) ?: null;
            if (empty($meta->ship_bldg)) $meta->ship_bldg = $ad->line2 ?? null;
        }

        $itemId = (int)($meta->item_id ?? 0);
        $item   = $itemId ? Item::find($itemId) : null;

        $shipZip  = ($meta->ship_zip  ?? '') ?: null;
        $shipAddr = ($meta->ship_addr ?? '') ?: null;
        $shipBldg = ($meta->ship_bldg ?? '') ?: null;
        $qty      = (int)($meta->quantity ?? 1);

        if ($session->payment_status === 'paid') {
            if ($item) {
                if (Schema::hasColumn('items', 'buyer_id')) $item->buyer_id = auth()->id();
                if (Schema::hasColumn('items', 'sold_at'))  $item->sold_at  = now();
                if (Schema::hasColumn('items', 'is_sold'))  $item->is_sold  = true;
                $item->save();

                $this->upsertPurchaseWithAddress(
                    $item,
                    auth()->id(),
                    'paid',
                    $shipZip,
                    $shipAddr,
                    $shipBldg,
                    $qty,
                    'card'
                );
            }

            return redirect('/mypage?tab=buy')
                ->with('status', '決済が完了しました。ありがとうございました。');
        }

        if (($meta->pay_method ?? 'card') === 'konbini') {
            $pi = \Stripe\PaymentIntent::retrieve($session->payment_intent);
            $voucherUrl =
                $pi->next_action->konbini_display_details->hosted_voucher_url
                ?? $pi->next_action->display_konbini_details->hosted_voucher_url
                ?? null;

            return view('purchases.konbini_pending', [
                'voucherUrl' => $voucherUrl,
                'item'       => $item,
            ]);
        }

        return redirect('/mypage?tab=buy')
            ->with('status', 'お支払い手続きを受け付けました。');
    }

    public function cancel()
    {
        return redirect('/mypage?tab=buy')->with('status', '決済をキャンセルしました。');
    }

    /**
     * 配送先住所の決定（プロフィール or セッションのカスタム住所）
     * 戻り: [zip, address, building, source]
     */
    private function resolveShippingAddress(Request $request): array
    {
        $source = $request->input('shipping_source', 'profile');
        $zip = $addr = $bldg = null;

        if ($source === 'custom') {
            // FIX: PurchaseController と同じキーで取得（なければ旧キーをフォールバック）
            $itemId = (int) $request->input('item_id');
            $custom = session("purchase.address.item_{$itemId}") ?? session('purchase.address');
            if (is_array($custom)) {
                $zip  = $custom['zip']      ?? null;
                $addr = $custom['address']  ?? null;
                $bldg = $custom['building'] ?? null;
            }

            // 住所が見つからなければプロフィールにフォールバック
            if (!$zip || !$addr) {
                $source  = 'profile';
            }
        }

        if ($source === 'profile') {
            $profile = Profile::where('user_id', auth()->id())->first();
            if ($profile) {
                $zip  = $profile->zip;
                $addr = $profile->address;
                $bldg = $profile->building ?? null;
            }
        }

        return [$zip, $addr, $bldg, $source];
    }

    /**
     * purchases に“住所込み”で保存
     */
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
            Log::info('Skip purchases insert (address required but missing)', compact('zip', 'address'));
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

        if (Schema::hasColumn('purchases', 'quantity'))        $vals['quantity']        = $quantity;
        // FIX: payment_method カラムにも対応
        if (Schema::hasColumn('purchases', 'payment_method'))  $vals['payment_method']  = $payMethod ?? '';
        if (Schema::hasColumn('purchases', 'pay_method'))      $vals['pay_method']      = $payMethod ?? '';
        if ($status === 'paid' && Schema::hasColumn('purchases', 'paid_at')) {
            $vals['paid_at'] = now();
        }

        try {
            \App\Models\Purchase::updateOrCreate(
                ['item_id' => $item->id, 'buyer_id' => $buyerId],
                $vals
            );
        } catch (\Throwable $e) {
            Log::warning('Purchase upsert failed but skipped', ['err' => $e->getMessage(), 'vals' => $vals]);
        }
    }
}
