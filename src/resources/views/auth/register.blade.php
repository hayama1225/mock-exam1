@extends('layouts.app')
@section('title', '会員登録')

@section('content')
<h2 class="section-title" style="text-align:center;">会員登録</h2>

<form method="POST" action="{{ route('register') }}" class="form" novalidate>
    @csrf

    <label for="name" class="muted">ユーザー名</label>
    <input id="name" name="name" type="text" class="input" value="{{ old('name') }}" autofocus>
    @error('name') <div class="error">{{ $message }}</div> @enderror

    <div style="height:12px;"></div>

    <label for="email" class="muted">メールアドレス</label>
    <input id="email" name="email" type="email" class="input" value="{{ old('email') }}">
    @error('email') <div class="error">{{ $message }}</div> @enderror

    <div style="height:12px;"></div>

    <label for="password" class="muted">パスワード</label>
    <input id="password" name="password" type="password" class="input">
    @error('password') <div class="error">{{ $message }}</div> @enderror

    <div style="height:12px;"></div>

    <label for="password_confirmation" class="muted">確認用パスワード</label>
    <input id="password_confirmation" name="password_confirmation" type="password" class="input">
    @error('password_confirmation') <div class="error">{{ $message }}</div> @enderror

    <div style="height:16px;"></div>

    <button type="submit" class="btn" style="width:100%;">登録する</button>

    <div style="text-align:center; margin-top:16px;">
        <a href="{{ route('login') }}">ログインはこちら</a>
    </div>
</form>
@endsection