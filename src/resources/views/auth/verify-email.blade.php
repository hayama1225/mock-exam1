<!doctype html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <title>メール認証</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="{{ asset('css/auth-verify.css') }}">
</head>

<body>
    <header class="header">
        {{-- ロゴだけのシンプルなヘッダー（src/public/images/logo.svg を使用） --}}
        <img src="{{ asset('images/logo.svg') }}" alt="logo" class="logo">
    </header>

    <main class="wrap">
        <p class="lead">
            登録していただいたメールアドレスに認証メールを送付しました。<br>
            メール認証を完了してください。
        </p>

        {{-- 上：認証チェック → OKならトップへ --}}
        <button id="goButton" type="button" class="btn-primary">認証はこちらから</button>

        {{-- 下：認証メールを再送する（リンク風ボタン / POST必要） --}}
        <form id="resendForm" method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="linklike">認証メールを再送する</button>
        </form>

        <p class="note">※ 別タブで「Verify Email Address」をクリックすると、自動でトップページへ移動します。</p>
    </main>

    <script>
        async function maybeGo() {
            try {
                const res = await fetch("{{ url('/email/verified-check') }}", {
                    credentials: 'same-origin'
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data.verified) {
                    window.location.replace("{{ url('/') }}");
                }
            } catch (_) {}
        }
        document.getElementById('goButton').addEventListener('click', async () => {
            await maybeGo();
            alert('まだ認証が完了していません。MailHog で「Verify Email Address」をクリックしてください。');
        });
        setInterval(maybeGo, 2000);
        window.addEventListener('focus', maybeGo);
    </script>
</body>

</html>