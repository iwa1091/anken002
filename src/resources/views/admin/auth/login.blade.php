@extends('layouts.admin-app') {{-- 管理者用の共通レイアウトを継承 --}}

@section('title', '管理者ログイン') {{-- ページタイトルを設定 --}}

@section('css')
    {{-- カスタムCSSファイルを読み込み --}}
    <link href="{{ asset('css/admin-log.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="login-container">
        <h2>管理者ログイン</h2>

        {{-- ログイン失敗時のメッセージ --}}
        @if (session('error'))
            <div class="alert-message">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login') }}">
            @csrf

            <div class="form-group">
                <label for="email">メールアドレス</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
                @error('email')
                    <p class="error-message">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">パスワード</label>
                <input type="password" id="password" name="password" required>
                @error('password')
                    <p class="error-message">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="login-button">ログイン</button>
        </form>
    </div>
@endsection
