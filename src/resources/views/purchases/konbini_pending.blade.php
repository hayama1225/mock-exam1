@extends('layouts.app')
@section('title','お支払い手続きのご案内')

@section('content')
<h2 class="section-title" style="margin:8px 0 16px;">お支払い手続きのご案内</h2>

@if($item)
<div class="detail" style="margin-bottom:12px;">
    <div class="card-image" style="max-width:240px;">
        @php
        $src = $item->image_path
        ? (\Illuminate\Support\Str::startsWith($item->image_path, ['http://','https://'])
        ? $item->image_path : asset('storage/'.$item->image_path))
        : null;
        @endphp
        @if($src)<img src="{{ $src }}" alt="" style="width:100%;">@endif
    </div>
    <div>
        <h3 class="section-title">{{ $item->name }}</h3>
        <div class="muted">小計：¥{{ number_format($item->price) }}</div>
    </div>
</div>
@endif

<p>コンビニでのお支払いが完了すると購入が確定します。支払期限にご注意ください。</p>

@if($voucherUrl)
<p style="margin:12px 0;">
    <a class="btn" href="{{ $voucherUrl }}" target="_blank" rel="noopener">
        バーコード／支払い番号を表示する
    </a>
</p>
@endif

<p style="margin-top:16px;">
    <a class="btn" href="{{ route('mypage.index') }}">マイページへ戻る</a>
</p>
@endsection
