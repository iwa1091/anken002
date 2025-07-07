{{-- 親レイアウトとして layouts.guest を継承 --}}
@extends('layouts.guest')

{{-- ページのタイトルを設定 --}}
@section('title', 'ログイン')

{{-- ログインページ固有のCSSを読み込む --}}
@section('css')
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

{{-- guest_content セクションにコンテンツを挿入 --}}
@section('guest_content')
    <div class="login-container"> {{-- メインコンテナ --}}
        {{-- ロゴとタイトル --}}
        <div class="login-header">
            <h2 class="login-title">
                ログイン
            </h2>
        </div>

        {{-- セッションステータスメッセージの表示 (例: ログイン失敗時の「ログイン情報が登録されていません」) --}}
        @if (session('status'))
            <div class="login-status-message" role="alert">
                {{ session('status') }}
            </div>
        @endif

        {{-- ログインフォーム --}}
        <form method="POST" action="{{ route('login') }}" class="login-form" novalidate>
            @csrf

            {{-- メールアドレス入力フィールド (FN007) --}}
            <div class="form-group">
                <label for="email" class="form-label">メールアドレス</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                       class="form-input @error('email') form-input--error @enderror">
                @error('email')
                    {{-- FN009: 未入力の場合のエラーメッセージ --}}
                    <p class="error-message">メールアドレスを入力してください</p>
                @enderror
            </div>

            {{-- パスワード入力フィールド (FN007) --}}
            <div class="form-group">
                <label for="password" class="form-label">パスワード</label>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                       class="form-input @error('password') form-input--error @enderror">
                @error('password')
                    {{-- FN009: 未入力の場合のエラーメッセージ --}}
                    <p class="error-message">パスワードを入力してください</p>
                @enderror
            </div>

            {{-- ログインボタン --}}
            <div class="login-button-container">
                <button type="submit" class="login-button">
                    ログインする
                </button>
            </div>
        </form>

        {{-- 会員登録への動線 (FN010) --}}
        <div class="register-link-container">
            <p class="register-text">
                <a href="{{ route('register') }}" class="register-link">
                    会員登録はこちら
                </a>
            </p>
        </div>
    </div>
@endsection
