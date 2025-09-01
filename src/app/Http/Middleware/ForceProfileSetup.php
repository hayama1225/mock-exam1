<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class ForceProfileSetup
{
    /** プロフィール未完了でも通して良いルート名 */
    private array $whitelist = [
        'items.index',
        'items.show',
        'login',
        'logout',
        'register',
        'verification.notice',
        'verification.verify',
        'verification.send',
        'password.request',
        'password.email',
        'password.update',
        'password.reset',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // ゲスト or MustVerifyEmail未実装 → 何もしない
        if (!$user || !($user instanceof MustVerifyEmail)) {
            return $next($request);
        }

        // メール未認証 → 何もしない（verified ミドルウェアが別途ブロック）
        if (!$user->hasVerifiedEmail()) {
            return $next($request);
        }

        // プロフィール未完了なら制限
        $profile = $user->profile;
        $isIncomplete = !$profile || is_null($profile->profile_completed_at);

        if ($isIncomplete) {
            // プロフィール編集系は常に許可
            if ($request->routeIs('profile.*')) {
                return $next($request);
            }

            // 明示ホワイトリストを許可
            if ($request->routeIs($this->whitelist)) {
                return $next($request);
            }

            // GET のみ意図URLを保存
            if ($request->isMethod('get')) {
                session()->put('url.intended', $request->fullUrl());
            }
            return redirect()->route('profile.edit');
        }

        return $next($request);
    }
}
