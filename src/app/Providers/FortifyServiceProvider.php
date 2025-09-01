<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Illuminate\Contracts\Auth\MustVerifyEmail; #追加
use App\Providers\RouteServiceProvider;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 登録直後は必ずメール認証誘導へ
        $this->app->singleton(\Laravel\Fortify\Contracts\RegisterResponse::class, function () {
            return new class implements \Laravel\Fortify\Contracts\RegisterResponse {
                public function toResponse($request)
                {
                    return redirect()->route('verification.notice'); // /email/verify
                }
            };
        });

        // ログイン後：プロフィール未完了なら /mypage/profile へ
        $this->app->singleton(\Laravel\Fortify\Contracts\LoginResponse::class, function () {
            return new class implements \Laravel\Fortify\Contracts\LoginResponse {
                public function toResponse($request)
                {
                    $user = auth()->user();

                    if ($user instanceof MustVerifyEmail && $user->hasVerifiedEmail()) {
                        $p = $user->profile;
                        if (!$p || is_null($p->profile_completed_at)) {
                            return redirect()->route('profile.edit');
                        }
                    }
                    return redirect()->intended(RouteServiceProvider::HOME); // => '/'
                }
            };
        });
    }

    public function boot(): void
    {
        // 認証・登録画面（Bladeは後で作成）
        Fortify::loginView(fn() => view('auth.login'));
        Fortify::registerView(fn() => view('auth.register'));
        Fortify::verifyEmailView(fn() => view('auth.verify-email'));
        Fortify::createUsersUsing(CreateNewUser::class); #追加

        // ログイン時のバリデーションを FormRequest で実行
        Fortify::authenticateUsing(function (Request $request) {
            $form = app(LoginRequest::class);
            Validator::make(
                $request->all(),
                $form->rules(),
                $form->messages(),
                $form->attributes()
            )->validate();

            // 資格情報チェック
            if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
                return Auth::user();
            }
            // 失敗メッセージ
            return null; // resources/lang/ja/auth.php の 'failed' を使用
        });
    }
}
