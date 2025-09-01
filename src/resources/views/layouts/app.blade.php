<!doctype html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title','COACHTECHフリマ')</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('css')
</head>

<body>
    <header class="site-header">
        <div class="container row">
            <div class="brand">
                <a href="{{ route('items.index') }}" aria-label="トップへ">
                    <img src="{{ asset('images/logo.svg') }}" alt="COACHTECH" class="logo">
                </a>
            </div>

            @unless (request()->routeIs(['login','register','verification.*']))
            <form class="search" method="GET" action="{{ route('items.index') }}">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="なにをお探しですか？">

                @if(request('tab'))
                <input type="hidden" name="tab" value="{{ request('tab') }}">
                @endif

                {{-- クリアボタン（qだけ外して現在のtabは維持） --}}
                @if(request()->filled('q'))
                @php
                $params = request()->has('tab') ? ['tab' => request('tab')] : [];
                @endphp
                <a class="link muted" href="{{ route('items.index', $params) }}" style="margin-left:8px">× クリア</a>
                @endif
            </form>
            @endunless

            @if(request('tab'))
            <input type="hidden" name="tab" value="{{ request('tab') }}">
            @endif

            {{-- クリアボタン（qだけ外して現在のtabは維持） --}}
            @if(request()->filled('q'))
            @php
            $params = request()->has('tab') ? ['tab' => request('tab')] : [];
            @endphp
            <a class="link muted" href="{{ route('items.index', $params) }}" style="margin-left:8px">× クリア</a>
            @endif
            </form>

            <nav class="nav-right">
                @auth
                {{-- 1) 見た目は aタグ、実態は JS で POST --}}
                <a href="{{ route('logout') }}" class="btn btn--dark"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    ログアウト
                </a>

                {{-- 2) 非表示のPOSTフォーム（CSRF付） --}}
                <form id="logout-form" method="POST" action="{{ route('logout') }}" style="display:none;">
                    @csrf
                </form>
                <a class="link" href="{{ route('mypage.index') }}">マイページ</a>
                <a class="btn btn--white" href="{{ route('items.create') }}">出品</a>
                @endauth

                @guest
                @unless (request()->routeIs(['login','register','verification.*']))
                <a class="link" href="{{ route('login') }}">ログイン</a>
                <a class="btn btn--white" href="{{ route('login') }}">出品</a>
                @endunless
                @endguest
            </nav>
        </div>
    </header>

    <div class="container">
        @if(session('status'))
        <div class="alert success">{{ session('status') }}</div>
        @endif

        @yield('content')
    </div>
    @stack('scripts')
</body>

</html>