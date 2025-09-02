<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sig     = $request->header('Stripe-Signature');
        $secret  = config('services.stripe.webhook_secret'); // .env に追加

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig, $secret);
        } catch (\Throwable $e) {
            return response('Invalid', 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                // カードはこの時点で多くが paid
                // コンビニは completed でも支払い完了は後続（payment_intent.succeeded）になる場合あり
                break;

            case 'payment_intent.succeeded':
                // コンビニ支払い完了通知。ここで注文を「支払い済み」に更新
                break;
        }
        return response('OK', 200);
    }
}
