@extends('layouts.app')
@section('title','購入手続き')

@section('content')
<h2 style="margin:8px 0 16px">購入手続き</h2>

<div class="detail">
    <div>
        <div class="card-image">
            @if($item->image_path)
            @php
            $src = \Illuminate\Support\Str::startsWith($item->image_path, ['http://','https://']) ? $item->image_path : asset('storage/'.$item->image_path);
            @endphp
            <img src="{{ $src }}" alt="">
            @endif
        </div>
        <h3 class="section-title">{{ $item->name }}</h3>
        <div class="muted">{{ $item->brand }}</div>
        <div style="font-size:24px;margin-top:8px">小計：¥{{ number_format($item->price) }}</div>
    </div>

    <div>
        <form method="POST" action="{{ route('checkout.create') }}">
            @csrf
            {{-- Stripeへ渡す最小セット --}}
            <input type="hidden" name="item_id" value="{{ $item->id }}">
            <input type="hidden" name="quantity" value="1">

            <h3 class="section-title">配送先</h3>
            @php
            $hasCustom = (bool)$custom;
            $profileAddr = $profile ? ($profile->zip.' '.$profile->address.' '.($profile->building??'')) : '未設定';
            $customAddr = $hasCustom ? ($custom['zip'].' '.$custom['address'].' '.($custom['building']??'')) : null;
            @endphp

            <label style="display:block;margin-bottom:6px">
                <input type="radio" name="shipping_source" value="profile" {{ old('shipping_source','profile')==='profile' ? 'checked' : '' }}>
                プロフィールの住所：<span class="muted">{{ $profileAddr }}</span>
            </label>

            @if($hasCustom)
            <label style="display:block;margin-bottom:6px">
                <input type="radio" name="shipping_source" value="custom" {{ old('shipping_source')==='custom' ? 'checked' : '' }}>
                登録した住所：<span class="muted">{{ $customAddr }}</span>
            </label>
            @endif

            <div style="margin:8px 0">
                <a class="btn" href="{{ route('purchase.address.edit',$item) }}">送付先住所を変更する</a>
            </div>
            @error('shipping_source')<div class="error">{{ $message }}</div>@enderror

            <h3 class="section-title">支払い方法</h3>
            <label style="display:block;margin-bottom:6px">
                <input type="radio" name="pay_method" value="konbini" {{ old('pay_method')==='konbini' ? 'checked' : '' }}>
                コンビニ支払い
            </label>
            <label style="display:block;margin-bottom:6px">
                <input type="radio" name="pay_method" value="card" {{ old('pay_method','card')==='card' ? 'checked' : '' }}>
                カード支払い
            </label>
            @error('pay_method')<div class="error">{{ $message }}</div>@enderror

            <div style="margin-top:16px">
                <button class="btn" type="submit">購入する</button>
            </div>
        </form>

    </div>
</div>
@endsection