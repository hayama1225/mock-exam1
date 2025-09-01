<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string', 'max:20'],
            'email'                 => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'              => ['required', 'string', 'min:8'],
            // 確認用は same:password（@error('password_confirmation') に出すため）
            'password_confirmation' => ['required', 'string', 'min:8', 'same:password'],
        ];
    }

    // 日本語メッセージ
    public function messages(): array
    {
        return [
            // 未入力
            'name.required'     => 'お名前を入力してください',
            'email.required'    => 'メールアドレスを入力してください',
            'email.email'       => 'メールアドレスはメール形式で入力してください',
            'password.required'               => 'パスワードを入力してください',
            'password_confirmation.required'  => '確認用パスワードを入力してください',

            // 規則違反
            'email.unique'                => 'このメールアドレスは既に登録されています',
            'password.min'                => 'パスワードは8文字以上で入力してください',
            'password_confirmation.min'   => '確認用パスワードは8文字以上で入力してください',
            'password_confirmation.same'  => 'パスワードと一致しません',

            // 汎用（このFormRequest内の string|min に適用される）
            'min.string'                      => ':attribute は :min 文字以上で入力してください',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'                  => 'ユーザー名',
            'email'                 => 'メールアドレス',
            'password'              => 'パスワード',
            'password_confirmation' => '確認用パスワード',
        ];
    }
}
