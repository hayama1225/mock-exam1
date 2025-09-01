<?php

return [
    'required' => ':attribute を入力してください',
    'image'    => ':attribute は画像ファイルを指定してください',
    'mimes'    => ':attribute は :values 形式でアップロードしてください',
    'array'    => ':attribute を正しく選択してください',
    'integer'  => ':attribute は整数で入力してください',
    'email'    => ':attribute はメール形式で入力してください',
    'min'      => [
        'numeric' => ':attribute は :min 以上で入力してください',
        'array'   => ':attribute は :min 件以上選択してください',
        'string'  => ':attribute は :min 文字以上で入力してください',
    ],
    'max'      => [
        'string'  => ':attribute は :max 文字以内で入力してください',
    ],
    'in'       => ':attribute の値が不正です',

    // ExhibitionRequest の個別メッセージ
    'custom' => [
        'image' => [
            'required' => '商品画像を選択してください',
            'image'    => '商品画像は画像ファイルを指定してください',
            'mimes'    => '商品画像は .jpeg もしくは .png を指定してください',
        ],
        'categories' => [
            'required' => 'カテゴリーを1つ以上選択してください',
            'array'    => 'カテゴリーの指定が不正です',
            'min'      => 'カテゴリーを1つ以上選択してください',
        ],
        'condition' => [
            'required' => '商品の状態を選択してください',
            'in'       => '商品の状態の値が不正です',
        ],
        'name' => [
            'required' => '商品名を入力してください',
            'max'      => '商品名は255文字以内で入力してください',
        ],
        'description' => [
            'required' => '商品の説明を入力してください',
            'max'      => '商品の説明は255文字以内で入力してください',
        ],
        'price' => [
            'required' => '販売価格を入力してください',
            'integer'  => '販売価格は整数で入力してください',
            'min'      => '販売価格は0円以上で入力してください',
        ],
    ],

    // フィールド名の日本語化
    'attributes' => [
        'image'       => '商品画像',
        'categories'  => 'カテゴリー',
        'condition'   => '商品の状態',
        'name'        => '商品名',
        'brand'       => 'ブランド名',
        'description' => '商品の説明',
        'price'       => '販売価格（円）',
        'email'                 => 'メールアドレス',
        'password'              => 'パスワード',
        'password_confirmation' => '確認用パスワード',
        'ユーザー名'             => 'ユーザー名',
    ],
];
