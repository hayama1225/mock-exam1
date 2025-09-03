@extends('layouts.app')
@section('title','コンビニ支払いのご案内')

@section('content')
<h2 class="section-title" style="margin:8px 0 16px;">コンビニ支払いのご案内</h2>

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
    <div style="margin-left:16px;">
        <h3 class="section-title">{{ $item->name }}</h3>
        <div class="muted">小計：¥{{ number_format($item->price) }}</div>
    </div>
</div>
@endif

<p>次のボタンから<strong>バウチャー（支払い番号／バーコード）</strong>を表示してください。新しいタブで開きます。</p>

<p style="margin:12px 0;">
    <a class="btn" href="{{ $sessionUrl }}" target="_blank" rel="noopener">
        バーコード／支払い番号を表示（新しいタブ）
    </a>
</p>

<p>支払い完了後、しばらくして自動で購入が確定します（この画面は閉じても構いません）。</p>

<p style="margin-top:16px;">
    <a class="btn" href="{{ route('mypage.index') }}">マイページへ戻る</a>
</p>
@endsection