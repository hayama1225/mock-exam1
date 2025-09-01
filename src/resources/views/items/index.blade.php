@extends('layouts.app')

@section('title','商品一覧')

@section('content')
<div class="tabs">
    <a href="{{ route('items.index', ['q' => request('q')]) }}"
        class="tab-link {{ $tab==='mylist' ? '' : 'active' }}">おすすめ</a>
    <a href="{{ route('items.index', ['tab'=>'mylist','q'=>request('q')]) }}"
        class="tab-link {{ $tab==='mylist' ? 'active' : '' }}">マイリスト</a>
</div>

{{-- 未ログインでマイリストを開いたときのガイダンス（ここ1か所だけでOK） --}}
@if(!auth()->check() && $tab === 'mylist')
<p class="muted">マイリストはログイン後に表示されます。</p>
@endif

@if($items->isEmpty())
<p class="muted">該当する商品がありません。</p>
@else
<div class="grid">
    @foreach($items as $item)
    <a class="card" href="{{ route('items.show',$item) }}">
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
        <div class="card-name">{{ $item->name }}</div>
    </a>
    @endforeach
</div>
@endif
@endsection