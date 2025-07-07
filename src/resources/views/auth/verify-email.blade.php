{{-- 親レイアウトとして layouts.guest を継承 --}}
@extends('layouts.guest')

{{-- ページのタイトルを設定 --}}
@section('title', 'メール認証')

{{-- 各ページ固有のCSSは、必要であればここに記述します。
     今回は基本的なレイアウトをlayouts/guest.blade.phpに依存します。 --}}
@section('css')
<link rel="stylesheet" href="{{ asset('css/verify_email.css') }}">
@endsection

{{-- guest.blade.phpの@yield('guest_content')にコンテンツを挿入 --}}
@section('guest_content')
    <div class="verify-email-container"> {{-- 全体を囲むコンテナ --}}
        <div class="verify-email-header">
            <p class="verify-email-description">
                登録していただいたメールアドレスに認証メールを送付しました。<br>
                メール認証を完了してください。
            </p>
        <p class="verify-email-button">
            <a href="{{ URL::signedRoute('verification.verify', ['id' => auth()->user()->id, 'hash' => sha1(auth()->user()->email)]) }}">
            認証はこちらから
            </a>
        </p>

        <div class="verify-email-actions">
            {{-- 認証メール再送フォーム --}}
            <form method="POST" action="{{ route('verification.resend') }}">
                @csrf
                <button type="submit" class="verify-email-resend-button">
                    認証メールを再送する
                </button>
            </form>
        </div>
    </div>
@endsection
