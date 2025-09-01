<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'zip' => ['required', 'regex:/^\d{3}-\d{4}$/'], // ハイフンあり8文字
            'address' => ['required', 'string'],
            'building' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'zip.required' => '郵便番号を入力してください',
            'zip.regex'    => '郵便番号はハイフンありの8文字',
            'address.required' => '住所を入力してください',
        ];
    }
}
