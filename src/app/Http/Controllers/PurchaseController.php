<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddressRequest;
use App\Http\Requests\PurchaseRequest;
use App\Models\Item;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Contracts\Auth\MustVerifyEmail; #追加


class PurchaseController extends Controller
{
    // 購入画面
    public function show(Item $item)
    {
        $this->authorizeView($item);

        $profile = Auth::user()->profile;
        $custom = Session::get($this->sessionKey($item->id)); // ['zip','address','building'] or null

        return view('purchases.show', [
            'item' => $item,
            'profile' => $profile,
            'custom' => $custom,
        ]);
    }

    // 購入送信 → Stripeへ or 模擬完了
    public function submit(PurchaseRequest $request, Item $item)
    {
        $this->authorizeView($item);

        $user = Auth::user();
        $shipping = $request->input('shipping_source') === 'custom'
            ? Session::get($this->sessionKey($item->id))
            : ['zip' => $user->profile->zip ?? '', 'address' => $user->profile->address ?? '', 'building' => $user->profile->building ?? null];

        if (!$shipping || empty($shipping['zip']) || empty($shipping['address'])) {
            return back()->withErrors(['shipping_source' => '配送先が未設定です。住所を登録してください。']);
        }

        // ▼ ここで Stripe鍵の有効性をチェック：未設定 or 仮値(XXXXX含む) なら模擬購入にフォールバック
        $secret = (string) config('services.stripe.secret');
        if (!$secret || preg_match('/X{4,}/i', $secret)) {
            return $this->completeWithoutStripe($item, $user->id, $shipping, $request->payment_method);
        }

        // ▼ Stripe 実行部は try-catch で安全に。失敗時は模擬購入へフォールバック
        try {
            \Stripe\Stripe::setApiKey($secret);

            $successUrl = route('purchase.success') . '?session_id={CHECKOUT_SESSION_ID}&item_id=' . $item->id;
            $cancelUrl  = route('purchase.cancel', ['item' => $item->id]);

            $session = \Stripe\Checkout\Session::create([
                'mode' => 'payment',
                'payment_method_types' => [$request->payment_method === 'konbini' ? 'konbini' : 'card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'jpy',
                        'product_data' => ['name' => $item->name],
                        'unit_amount' => $item->price,
                    ],
                    'quantity' => 1,
                ]],
                'success_url' => $successUrl,
                'cancel_url'  => $cancelUrl,
                'metadata' => [
                    'item_id' => (string)$item->id,
                    'user_id' => (string)$user->id,
                    'zip' => $shipping['zip'],
                    'address' => $shipping['address'],
                    'building' => $shipping['building'] ?? '',
                    'payment_method' => $request->payment_method,
                ],
            ]);

            // pendingで仮登録
            Purchase::create([
                'item_id' => $item->id,
                'buyer_id' => $user->id,
                'amount' => $item->price,
                'payment_method' => $request->payment_method,
                'zip' => $shipping['zip'],
                'address' => $shipping['address'],
                'building' => $shipping['building'] ?? null,
                'stripe_session_id' => $session->id,
                'status' => 'pending',
            ]);

            return redirect($session->url);
        } catch (\Throwable $e) {
            report($e);
            // エラー時は模擬完了
            return $this->completeWithoutStripe($item, $user->id, $shipping, $request->payment_method);
        }
    }

    // 住所変更フォーム
    public function editAddress(Item $item)
    {
        $this->authorizeView($item);

        $profile = Auth::user()->profile;
        $current = Session::get($this->sessionKey($item->id));

        return view('purchases.address', [
            'item' => $item,
            'profile' => $profile,
            'current' => $current,
        ]);
    }

    // 住所変更の保存（セッション保存）※文言を統一
    public function updateAddress(AddressRequest $request, Item $item)
    {
        $this->authorizeView($item);

        $data = $request->only('zip', 'address', 'building');
        Session::put($this->sessionKey($item->id), $data);

        return redirect()
            ->route('purchase.show', $item)
            ->with('status', '送付先住所を更新しました。');
    }

    // Stripe 成功：マイページ（購入タブ）へ
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');
        $itemId    = (int) $request->query('item_id');

        $purchase = Purchase::where('stripe_session_id', $sessionId)
            ->where('item_id', $itemId)
            ->first();

        if (!$purchase) {
            return redirect()->route('items.index')->with('status', '購入処理が見つかりませんでした。');
        }

        // 決済完了（本来は webhook 推奨）
        $purchase->update([
            'status' => 'paid',
            'paid_at' => now(),
            'stripe_payment_intent' => $purchase->stripe_payment_intent ?: null,
        ]);

        // アイテムを Sold に
        $item = $purchase->item;
        $item->update([
            'buyer_id' => $purchase->buyer_id,
            'sold_at'  => now(),
        ]);

        // 住所キャッシュを消す
        Session::forget($this->sessionKey($item->id));

        return redirect()
            ->route('mypage.index', ['tab' => 'buy'])
            ->with('status', '購入が完了しました。');
    }

    // Stripe キャンセル：元の購入画面へ
    public function cancel(Request $request, int $item)
    {
        return redirect()
            ->route('purchase.show', $item)
            ->with('status', '決済をキャンセルしました。');
    }

    // ---------------- private helpers ----------------
    private function sessionKey(int $itemId): string
    {
        return "purchase.address.item_{$itemId}";
    }

    private function authorizeView(Item $item): void
    {
        $user = Auth::user();

        // 未ログイン
        abort_unless($user, 403);

        // メール未認証は不可（型安全にチェック）
        if (!($user instanceof MustVerifyEmail) || !$user->hasVerifiedEmail()) {
            abort(403);
        }

        // 自分の商品は買えない／Sold は不可
        abort_if($item->seller_id === $user->id, 403);
        abort_if($item->is_sold, 403);
    }

    // （Stripe未設定/失敗時）模擬購入の完了：マイページ（購入タブ）へ
    private function completeWithoutStripe(Item $item, int $buyerId, array $shipping, string $method)
    {
        $purchase = Purchase::create([
            'item_id'   => $item->id,
            'buyer_id'  => $buyerId,
            'amount'    => $item->price,
            'payment_method' => $method,
            'zip'       => $shipping['zip'],
            'address'   => $shipping['address'],
            'building'  => $shipping['building'] ?? null,
            'status'    => 'paid',
            'paid_at'   => now(),
        ]);

        $item->update(['buyer_id' => $buyerId, 'sold_at' => now()]);

        Session::forget($this->sessionKey($item->id));

        return redirect()
            ->route('mypage.index', ['tab' => 'buy'])
            ->with('status', '購入が完了しました。');
    }
}
