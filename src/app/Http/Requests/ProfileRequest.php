<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // return true;
        return auth()->check(); #(任意)authorizeメソッドをより厳格に。「拡張子偽装の非画像」を弾けるらしい
    }

    public function rules(): array
    {
        return [
            'avatar'   => ['nullable', 'image', 'mimes:jpeg,png'],
            'username' => ['required', 'string', 'max:20'],
            'zip'      => ['required', 'regex:/^\d{3}-\d{4}$/'],
            'address'  => ['required', 'string'],
            'building' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.image'   => 'プロフィール画像は画像ファイルを選択してください',
            'avatar.mimes'   => 'プロフィール画像は.jpegまたは.pngを選択してください',
            'username.required' => 'ユーザー名を入力してください',
            'username.max'      => 'ユーザー名は20文字以内で入力してください',
            'zip.required'      => '郵便番号を入力してください',
            'zip.regex'         => '郵便番号はハイフンありの8文字（例：123-4567）で入力してください',
            'address.required'  => '住所を入力してください',
        ];
    }
}
