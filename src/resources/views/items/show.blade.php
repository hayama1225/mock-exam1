@extends('layouts.app')

@section('title',$item->name)

@push('css')
<link rel="stylesheet" href="{{ asset('css/items.css') }}">
@endpush

@section('content')
<div class="detail">
    <div class="product-image">
        <div class="card-image">
            @if($item->image_path)
            @php
            $src = \Illuminate\Support\Str::startsWith($item->image_path, ['http://','https://'])
            ? $item->image_path
            : asset('storage/'.$item->image_path);
            @endphp
            <img src="{{ $src }}" alt="">
            @endif
        </div>
        @if($item->is_sold)
        <span class="badge-sold">Sold</span>
        @endif
    </div>

    <div>
        <h2 style="margin:0 0 8px 0">{{ $item->name }}</h2>
        <div class="muted" style="margin-bottom:8px">{{ $item->brand }}</div>
        <div style="font-size:24px;margin-bottom:12px">
            ¥{{ number_format($item->price) }} <span class="muted" style="font-size:12px;">(税込)</span>
        </div>

        @php
        // コメント数は withCount('comments') があればそれを優先、無ければコレクションから数える
        $commentsCount = isset($item->comments_count) ? $item->comments_count : ($item->comments->count() ?? 0);
        @endphp

        <div class="actions">
            {{-- いいね（ログイン状態に応じてフォーム／リンクを出し分け） --}}
            @auth
            <form method="POST" action="{{ route('items.like',$item) }}" class="action" aria-label="{{ $liked ? 'いいねを解除' : 'いいね' }}">
                @csrf
                <button class="action-button" type="submit">
                    <img class="icon" src="{{ asset($liked ? 'img/icons/like_fill.svg' : 'img/icons/like.svg') }}" alt="">
                </button>
                <span class="count">{{ $item->likes_count }}</span>
            </form>
            @else
            <a class="action" href="{{ route('login') }}" aria-label="いいねにはログインが必要です">
                <img class="icon" src="{{ asset('img/icons/like.svg') }}" alt="">
                <span class="count">{{ $item->likes_count }}</span>
            </a>
            @endauth

            {{-- コメント（コメント欄へジャンプ） --}}
            <a class="action" href="#comments" aria-label="コメント一覧へ移動">
                <img class="icon" src="{{ asset('img/icons/comment.svg') }}" alt="">
                <span class="count">{{ $commentsCount }}</span>
            </a>
        </div>


        {{-- ★ 購入ボタンの表示制御（Sold/自分の出品は非表示） --}}
        @php
        $isMine = auth()->check() && auth()->id() === $item->seller_id;
        @endphp

        <div style="margin:16px 0;">
            @if($item->is_sold)
            <div class="muted">この商品はSoldです。</div>
            @elseif($isMine)
            <div class="muted">これはあなたの出品です。</div>
            @else
            @auth
            <a class="btn" href="{{ route('purchase.show', $item) }}">購入手続きへ</a>
            @else
            <a class="btn" href="{{ route('login') }}">購入手続きへ</a>
            @endauth
            @endif
        </div>

        <h3 class="section-title">商品説明</h3>
        <div class="muted" style="white-space:pre-wrap">{{ $item->description }}</div>

        <h3 class="section-title">商品の情報</h3>
        <div class="muted">カテゴリ：</div>
        <div class="chips" style="margin:6px 0 0;">
            @foreach($item->categories as $c)
            <span class="chip">{{ $c->name }}</span>
            @endforeach
        </div>
        <div class="muted" style="margin-top:6px">商品の状態：{{ $item->condition }}</div>

        <h3 id="comments" class="section-title">商品のコメント</h3>
        @auth
        <form class="form" method="POST" action="{{ route('items.comments.store', $item) }}">
            @csrf
            <textarea class="input" name="body" rows="4" placeholder="こちらにコメントが入ります。">{{ old('body') }}</textarea>
            @error('body')<div class="error">{{ $message }}</div>@enderror
            <div class="form-actions">
                <button class="btn" type="submit">コメントを送信する</button>
            </div>
        </form>
        @else
        <p class="muted">コメントするには <a href="{{ route('login') }}">ログイン</a> が必要です。</p>
        @endauth
        <div>
            @forelse($item->comments as $cm)
            <div class="comment">
                <div class="comment-meta">{{ $cm->user->name }}・{{ $cm->created_at->format('Y-m-d H:i') }}</div>
                <div>{{ $cm->body }}</div>
            </div>
            @empty
            <div class="muted">まだコメントはありません。</div>
            @endforelse
        </div>
    </div>
</div>
@endsection