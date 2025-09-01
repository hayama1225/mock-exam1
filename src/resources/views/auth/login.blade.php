@extends('layouts.app')
@section('title', 'ログイン')

@section('content')
<h2 class="section-title" style="text-align:center;">ログイン</h2>

<form method="POST" action="{{ route('login') }}" class="form" novalidate>
    @csrf

    <label for="email" class="muted">メールアドレス</label>
    <input id="email" name="email" type="email" class="input" value="{{ old('email') }}" autofocus>
    @error('email')<div class="error">{{ $message }}</div>@enderror

    <div style="height:12px;"></div>

    <label for="password" class="muted">パスワード</label>
    <input id="password" name="password" type="password" class="input">
    @error('password')<div class="error">{{ $message }}</div>@enderror

    <div style="height:16px;"></div>

    <button type="submit" class="btn" style="width:100%;">ログインする</button>

    <div style="text-align:center; margin-top:16px;">
        <a href="{{ route('register') }}">会員登録はこちら</a>
    </div>
</form>
@endsection