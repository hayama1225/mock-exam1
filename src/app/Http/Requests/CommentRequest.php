<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:255'], // 要件：必須、最大255
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => '商品コメントを入力してください',
            'body.max'      => '商品コメントは255文字以内で入力してください',
        ];
    }
}
