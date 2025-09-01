<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ExhibitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            // 画像：どちらか片方があればOKにするため image は nullable に変更
            'image'         => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png', 'max:5120'],
            'image_tmp'     => ['nullable', 'string'],

            'categories'    => ['required', 'array', 'min:1'],
            'categories.*'  => ['exists:categories,id'],

            'condition'     => ['required', 'in:良好,目立った傷や汚れなし,やや傷や汚れあり,状態が悪い'],
            'name'          => ['required', 'string', 'max:255'],
            'brand'         => ['nullable', 'string', 'max:255'],
            'description'   => ['required', 'string', 'max:255'],
            'price'         => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($v) {
            // 画像のどちらも無いときにまとめてエラー
            if (!$this->hasFile('image') && !$this->filled('image_tmp')) {
                $v->errors()->add('image', '画像を選択してください。');
            }
        });
    }

    public function messages(): array
    {
        return [
            'categories.required' => 'カテゴリーを1つ以上選択してください。',
            'categories.min'      => 'カテゴリーを1つ以上選択してください。',
            'price.integer'       => '販売価格は数字で入力してください。',
            'price.min'           => '販売価格は0円以上で入力してください。',
            'image.image'         => '画像ファイルを選択してください。',
            'image.mimes'         => '画像はjpeg/jpg/png形式でアップロードしてください。',
            'image.max'           => '画像サイズは5MB以下にしてください。',
        ];
    }
}
