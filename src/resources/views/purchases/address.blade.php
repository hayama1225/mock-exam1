@extends('layouts.app')
@section('title','送付先住所の変更')

@section('content')
<h2 style="margin:8px 0 16px">送付先住所の変更</h2>

<form class="form" method="POST" action="{{ route('purchase.address.update',$item) }}">
    @csrf
    <div style="margin-bottom:12px">
        <label>郵便番号（例：123-4567）</label>
        <input class="input" type="text" name="zip" value="{{ old('zip', optional($current)['zip'] ?? optional($profile)->zip) }}" placeholder="123-4567">
        @error('zip')<div class="error">{{ $message }}</div>@enderror
    </div>

    <div style="margin-bottom:12px">
        <label>住所</label>
        <input class="input" type="text" name="address" value="{{ old('address', optional($current)['address'] ?? optional($profile)->address) }}">
        @error('address')<div class="error">{{ $message }}</div>@enderror
    </div>

    <div style="margin-bottom:16px">
        <label>建物名</label>
        <input class="input" type="text" name="building" value="{{ old('building', optional($current)['building'] ?? optional($profile)->building) }}">
        @error('building')<div class="error">{{ $message }}</div>@enderror
    </div>

    <button class="btn" type="submit">住所を保存する</button>
    <a href="{{ route('purchase.show',$item) }}" style="margin-left:12px;">戻る</a>
</form>
@endsection