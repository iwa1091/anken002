{{-- 親レイアウトとして layouts.guest を継承 --}}
@extends('layouts.guest')

{{-- ページのタイトルを設定 --}}
@section('title', '会員登録')

{{-- 各ページ固有のCSSを挿入するためのプレースホルダ。layouts/guest.blade.php で @yield('css') を使用しないため、直接ここで読み込む --}}
@section('css')
<link rel="stylesheet" href="{{ asset('css/register.css') }}">
@endsection

{{-- guest.blade.phpの@yield('guest_content')にコンテンツを挿入 --}}
@section('guest_content')
    {{-- register.css の .register クラスを適用 --}}
    <div class="register">
        {{-- register.css の .register__header クラスを適用 --}}
        <div class="register__header">
            <h2 class="register__title">会員登録</h2>
        </div>
        {{-- register.css の .register__form クラスを適用 --}}
        <form method="POST" action="{{ route('register') }}" novalidate class="register__form">
            @csrf

            <!-- 名前入力欄 -->
            {{-- register.css の .register__field クラスを適用 --}}
            <div class="register__field">
                {{-- register.css の .register__label クラスを適用 --}}
                <label for="name" class="register__label">名前</label>
                {{-- register.css の .register__input と .register__input--error クラスを適用 --}}
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                       class="register__input @error('name') register__input--error @enderror">
                @error('name')
                    {{-- register.css の .register__error クラスを適用 --}}
                    <p class="register__error">{{ $message }}</p>
                @enderror
            </div>

            <!-- メールアドレス入力欄 -->
            {{-- register.css の .register__field クラスを適用 --}}
            <div class="register__field">
                {{-- register.css の .register__label クラスを適用 --}}
                <label for="email" class="register__label">メールアドレス</label>
                {{-- register.css の .register__input と .register__input--error クラスを適用 --}}
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                       class="register__input @error('email') register__input--error @enderror">
                @error('email')
                    {{-- register.css の .register__error クラスを適用 --}}
                    <p class="register__error">{{ $message }}</p>
                @enderror
            </div>

            <!-- パスワード入力欄 -->
            {{-- register.css の .register__field クラスを適用 --}}
            <div class="register__field">
                {{-- register.css の .register__label クラスを適用 --}}
                <label for="password" class="register__label">パスワード</label>
                {{-- register.css の .register__input と .register__input--error クラスを適用 --}}
                <input id="password" type="password" name="password" required autocomplete="new-password"
                       class="register__input @error('password') register__input--error @enderror">
                @error('password')
                    {{-- register.css の .register__error クラスを適用 --}}
                    <p class="register__error">{{ $message }}</p>
                @enderror
            </div>

            <!-- パスワード確認入力欄 -->
            {{-- register.css の .register__field クラスを適用 --}}
            <div class="register__field">
                {{-- register.css の .register__label クラスを適用 --}}
                <label for="password_confirmation" class="register__label">パスワード確認</label>
                {{-- register.css の .register__input クラスを適用 --}}
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                       class="register__input">
            </div>

            <!-- 登録ボタン -->
            {{-- register.css の .register__button クラスを適用 --}}
            <div class="register__button">
                {{-- register.css の .register__submit クラスを適用 --}}
                <button type="submit" class="register__submit">
                    登録する
                </button>
            </div>
        </form>

        <!-- ログインページへのリンク -->
        {{-- register.css の .register__login-link クラスを適用 --}}
        <div class="register__login-link">
            <p>
                <a href="{{ route('login') }}">
                    ログインはこちら
                </a>
            </p>
        </div>
    </div>
@endsection
