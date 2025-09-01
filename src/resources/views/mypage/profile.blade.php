@extends('layouts.app')
@section('title', 'プロフィール設定')

@section('content')
<h2 class="section-title" style="text-align:center;">プロフィール設定</h2>

<form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="form">
    @csrf

    {{-- プロフィール画像（丸いプレビュー＋選択ボタン） --}}
    <label class="muted">プロフィール画像</label>
    @php
    $avatar = $profile->avatar_path ? asset('storage/'.$profile->avatar_path) : null;
    @endphp
    <div class="row" style="gap:16px; justify-content:flex-start; align-items:center; margin:8px 0 4px;">
        <div style="width:80px; height:80px; border-radius:50%; overflow:hidden; background:#f3f4f6;">
            @if($avatar)
            <img id="avatarPreview" src="{{ $avatar }}" alt="avatar" style="width:100%; height:100%; object-fit:cover;">
            @else
            <img id="avatarPreview" src="" alt="" style="display:none; width:100%; height:100%; object-fit:cover;">
            @endif
        </div>

        {{-- input[type=file] は隠して、ラベルをボタン化 --}}
        <input id="avatar" type="file" name="avatar" accept=".jpeg,.jpg,.png" style="display:none;">
        <label for="avatar" class="btn" style="background:#fff; color:#ef4444; border:1px solid #ef4444;">
            画像を選択する
        </label>
    </div>
    @error('avatar')<div class="error">{{ $message }}</div>@enderror

    <div style="height:12px;"></div>

    <label for="username" class="muted">ユーザー名</label>
    <input id="username" type="text" name="username" class="input" value="{{ old('username', $profile->username) }}" required>
    @error('username')<div class="error">{{ $message }}</div>@enderror

    <div style="height:12px;"></div>

    <label for="zip" class="muted">郵便番号</label>
    <input id="zip" type="text" name="zip" class="input" placeholder="123-4567" value="{{ old('zip', $profile->zip) }}">
    @error('zip')<div class="error">{{ $message }}</div>@enderror

    <div style="height:12px;"></div>

    <label for="address" class="muted">住所</label>
    <input id="address" type="text" name="address" class="input" value="{{ old('address', $profile->address) }}">
    @error('address')<div class="error">{{ $message }}</div>@enderror

    <div style="height:12px;"></div>

    <label for="building" class="muted">建物名</label>
    <input id="building" type="text" name="building" class="input" value="{{ old('building', $profile->building) }}">
    @error('building')<div class="error">{{ $message }}</div>@enderror

    <div style="height:16px;"></div>

    <button type="submit" class="btn" style="width:100%;">更新する</button>
</form>

{{-- 選択した画像を即時プレビュー --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('avatar');
        const img = document.getElementById('avatarPreview');
        if (!input) return;
        input.addEventListener('change', function() {
            const file = this.files && this.files[0];
            if (!file) return;
            const url = URL.createObjectURL(file);
            img.src = url;
            img.style.display = 'block';
        });
    });
</script>
@endsection