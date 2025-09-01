<!doctype html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <title>メール認証が完了しました</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="{{ asset('css/auth-verify.css') }}">
    <style>
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            color: #111;
        }

        .header {
            height: 64px;
            display: flex;
            align-items: center;
            padding: 0 20px;
            border-bottom: 1px solid #eee;
        }

        .logo {
            height: 28px;
        }

        .wrap {
            max-width: 720px;
            margin: 56px auto;
            text-align: center;
            padding: 0 16px;
        }

        .lead {
            line-height: 1.9;
            margin: 0 0 16px;
        }

        .sub {
            color: #666;
            font-size: 14px;
        }

        .btn {
            margin-top: 20px;
            padding: 10px 18px;
            border: 1px solid #d0d0d0;
            border-radius: 10px;
            background: #f2f2f2;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <header class="header">
        <img src="{{ asset('images/logo.svg') }}" alt="logo" class="logo">
    </header>
    <main class="wrap">
        <h1 class="lead">メール認証が完了しました。</h1>
        <p class="sub">元のページで状態が反映されます。このタブは閉じても大丈夫です。</p>
        <button class="btn" onclick="window.close()">タブを閉じる</button>
    </main>
</body>

</html>