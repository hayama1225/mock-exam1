@extends('layouts.app')

@section('title','マイページ')

@push('css')
<link rel="stylesheet" href="{{ asset('css/mypage.css') }}">
@endpush

@section('content')
@php
// ★ コントローラから渡っていなくても落ちないようにフォールバック
/** @var \App\Models\User|null $user */
$user = $user ?? auth()->user();

// タブ：未指定なら購入一覧（buy）をデフォルト
$tab = $tab ?? request('tab', 'buy');

// 出品一覧フォールバック
$items = $items ?? ($user
? $user->items()->orderBy('id','asc')->get()
: collect());

// 購入一覧フォールバック（purchases 経由で item を表示）
$purchases = $purchases ?? ($user
? \App\Models\Purchase::with('item')->where('buyer_id', $user->id)->orderBy('id','asc')->get()
: collect());
@endphp

{{-- プロフィールヘッダ --}}
@php
$avatar = optional($user->profile)->avatar_path;
$avatarUrl = $avatar ? asset('storage/'.$avatar) : null;
$displayName = optional($user->profile)->username ?? $user->name; // 表示名
$email = $user->email; // メールを表示
@endphp

<div class="profile-header" style="margin-bottom:16px;">
    <div class="profile-avatar">
        @if($avatarUrl)
        <img src="{{ $avatarUrl }}" alt="avatar" style="width:100%; height:100%; object-fit:cover;">
        @endif
    </div>

    <div class="profile-main">
        <div class="profile-info">
            <div class="muted">ユーザー名</div>
            <div class="profile-name">{{ $displayName }}</div>
            <div class="profile-address">{{ $email }}</div>
        </div>

        <div class="profile-actions">
            <a class="btn" href="{{ route('profile.edit') }}">プロフィールを編集</a>
        </div>
    </div>
</div>

{{-- タブ --}}
<div class="tabs">
    <a href="{{ route('mypage.index', ['tab'=>'buy']) }}" class="tab-link {{ $tab==='buy'  ? 'active' : '' }}">購入した商品一覧</a>
    <a href="{{ route('mypage.index', ['tab'=>'sell']) }}" class="tab-link {{ $tab==='sell' ? 'active' : '' }}">出品した商品一覧</a>
</div>

{{-- 一覧 --}}
@if($tab==='buy')
@if($purchases->isEmpty())
<p class="muted">購入した商品はありません。</p>
@else
<div class="grid">
    @foreach($purchases as $p)
    @php $it = $p->item; @endphp
    @if($it)
    <a class="card" href="{{ route('items.show',$it) }}">
        <div class="card-image">
            @if($it->image_path)
            @php
            $src = \Illuminate\Support\Str::startsWith($it->image_path, ['http://','https://'])
            ? $it->image_path : asset('storage/'.$it->image_path);
            @endphp
            <img src="{{ $src }}" alt="">
            @endif
        </div>
        @if($it->is_sold)
        <span class="badge-sold">Sold</span>
        @endif
        <div class="card-name">{{ $it->name }}</div>
    </a>
    @endif
    @endforeach
</div>
@endif
@else
@if($items->isEmpty())
<p class="muted">出品した商品はありません。</p>
@else
<div class="grid">
    @foreach($items as $it)
    <a class="card" href="{{ route('items.show',$it) }}">
        <div class="card-image">
            @if($it->image_path)
            @php
            $src = \Illuminate\Support\Str::startsWith($it->image_path, ['http://','https://'])
            ? $it->image_path : asset('storage/'.$it->image_path);
            @endphp
            <img src="{{ $src }}" alt="">
            @endif
        </div>
        @if($it->is_sold)
        <span class="badge-sold">Sold</span>
        @endif
        <div class="card-name">{{ $it->name }}</div>
    </a>
    @endforeach
</div>
@endif
@endif
@endsection