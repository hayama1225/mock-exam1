@extends('layouts.app')
@section('title','商品の出品')

@push('css')
<link rel="stylesheet" href="{{ asset('css/sell.css') }}">
@endpush

@section('content')
<h2 class="sell-title">商品の出品</h2>

<form class="form sell-form" method="POST" action="{{ route('items.store') }}" enctype="multipart/form-data">
    @csrf

    {{-- 商品画像：中央の赤枠ボタン --}}
    <div class="form-row">
        <label>商品画像 <span class="required">*</span></label>
        <div class="image-uploader">
            {{-- プレビュー（oldに一時パスがあれば初期表示） --}}
            <img id="image-preview" class="image-preview"
                @if(old('image_tmp'))
                src="{{ asset('storage/'.old('image_tmp')) }}" style="display:block"
                @endif
                alt="選択画像プレビュー">

            {{-- 実ファイル選択 --}}
            <input id="image" class="file-input" type="file" name="image" accept=".jpeg,.jpg,.png">
            <label for="image" class="file-button">画像を選択する</label>

            {{-- 一時保存用のパスを保持（後述のAJAXで自動セット） --}}
            <input type="hidden" name="image_tmp" id="image_tmp" value="{{ old('image_tmp') }}">
        </div>
        @error('image')<div class="error">{{ $message }}</div>@enderror
    </div>

    {{-- 商品の詳細 --}}
    <h3 class="sell-subtitle">商品の詳細</h3>

    {{-- カテゴリー（複数） --}}
    <div class="form-row">
        <label>カテゴリー <span class="required">*</span></label>
        <div class="chip-list">
            @foreach($categories as $c)
            <label class="chip">
                <input type="checkbox" name="categories[]" value="{{ $c->id }}" {{ in_array($c->id, (array)old('categories',[])) ? 'checked' : '' }}>
                <span>{{ $c->name }}</span>
            </label>
            @endforeach
        </div>
        @error('categories')<div class="error">{{ $message }}</div>@enderror
    </div>

    {{-- 商品の状態 --}}
    <div class="form-row">
        <label>商品の状態 <span class="required">*</span></label>
        <select class="input" name="condition">
            <option value="">選択してください</option>
            @foreach($conditions as $cond)
            <option value="{{ $cond }}" {{ old('condition')===$cond ? 'selected' : '' }}>{{ $cond }}</option>
            @endforeach
        </select>
        @error('condition')<div class="error">{{ $message }}</div>@enderror
    </div>

    {{-- 商品名 --}}
    <div class="form-row">
        <label>商品名 <span class="required">*</span></label>
        <input class="input" type="text" name="name" value="{{ old('name') }}">
        @error('name')<div class="error">{{ $message }}</div>@enderror
    </div>

    {{-- ブランド名 --}}
    <div class="form-row">
        <label>ブランド名</label>
        <input class="input" type="text" name="brand" value="{{ old('brand') }}">
        @error('brand')<div class="error">{{ $message }}</div>@enderror
    </div>

    {{-- 商品の説明 --}}
    <div class="form-row">
        <label>商品の説明 <span class="required">*</span></label>
        <textarea class="input" name="description" rows="4">{{ old('description') }}</textarea>
        @error('description')<div class="error">{{ $message }}</div>@enderror
    </div>

    {{-- 販売価格：（円）表記を外し、placeholder に ￥ --}}
    <div class="form-row">
        <label>販売価格 <span class="required">*</span></label>
        <input class="input" type="text" name="price" inputmode="numeric" pattern="[0-9]*" placeholder="￥" value="{{ old('price') }}">
        @error('price')<div class="error">{{ $message }}</div>@enderror
    </div>

    <div class="form-actions">
        <button class="btn" type="submit">出品する</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('image');
        const preview = document.getElementById('image-preview');
        const tmp = document.getElementById('image_tmp');
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const uploadUrl = "{{ route('upload.tmp') }}";

        const show = (url) => {
            preview.src = url;
            preview.style.display = 'block';
        };

        input.addEventListener('change', async () => {
            const file = input.files && input.files[0];
            if (!file) return;

            // 即時プレビュー
            show(URL.createObjectURL(file));

            // 一時アップロード
            const fd = new FormData();
            fd.append('image', file);
            const res = await fetch(uploadUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token
                },
                body: fd
            });
            if (res.ok) {
                const data = await res.json();
                tmp.value = data.path; // 例: tmp/xxxx.jpg
                show(data.url); // 例: /storage/tmp/xxxx.jpg
            } else {
                console.error('一時アップロード失敗', res.status);
            }
        });
    });
</script>
@endpush