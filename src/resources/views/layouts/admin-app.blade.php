<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- ページタイトルを子ビューから設定し、デフォルトは'管理者画面'とする --}}
    <title>@yield('title', '管理者画面')</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}"> {{-- 共通のapp.cssも引き続き使用 --}}
    {{-- Google Fonts (Inter) は、CSSファイル内で @import するか、ここに残す --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    {{-- 各ページ固有のCSSを挿入するためのプレースホルダ --}}
    @yield('css')
</head>
<body>
    <header class="header">
        <div class="header__inner">
            <!-- ロゴ -->
            <div class="header__logo">
                <img src="{{ asset('images/logo.svg') }}" alt="ロゴ">
            </div>
            @php
                use Illuminate\Support\Facades\Auth; // Authファサードを使用
                use Illuminate\Support\Facades\Route; // Routeファサードを使用
                $currentRouteName = Route::currentRouteName(); // 現在のルート名を取得
                // ナビゲーションを非表示にする認証関連ルートのリスト
                // 管理者ログイン画面ではヘッダーを表示しないため、ここに追加
                $excludedRoutes = [
                    'admin.login',
                ];
            @endphp
            {{-- 現在のルートが除外リストに含まれていない場合にナビゲーションを表示 --}}
            @if (!in_array($currentRouteName, $excludedRoutes))
                <nav class="header__nav">
                    <ul class="header__list">
                        @auth('admin') {{-- 管理者ガードで認証している場合のみメニューを表示 --}}
                            {{-- 管理者専用画面メニュー --}}
                            <li class="header__list-item">
                                <a href="{{ route('admin.dashboard') }}" class="header__form--mypage">勤怠一覧</a>
                            </li>
                            <li class="header__list-item">
                                <a href="{{ route('admin.staff.list') }}" class="header__form--list">スタッフ一覧</a>
                            </li>
                            <li class="header__list-item">
                                <a href="{{ route('admin.stamp_correction_request.list') }}" class="header__form--list">申請一覧</a>
                            </li>
                            {{-- ログアウトは管理者用 --}}
                            <li class="header__list-item">
                                <form action="{{ route('admin.logout') }}" method="post">
                                    @csrf
                                    <button class="header__form--logout" type="submit">ログアウト</button>
                                </form>
                            </li>
                        @endauth
                    </ul>
                </nav>
            @endif
        </div>
    </header>

    <main>
        {{-- 各ページのメインコンテンツを挿入 --}}
        @yield('content')
    </main>
</body>
</html>
