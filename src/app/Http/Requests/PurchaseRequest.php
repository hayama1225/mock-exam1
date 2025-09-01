<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'in:card,konbini'], // 支払い方法：選択必須
            'shipping_source' => ['required', 'in:profile,custom'], // 配送先：選択必須
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => '支払い方法を選択してください',
            'shipping_source.required' => '配送先を選択してください',
        ];
    }
}
