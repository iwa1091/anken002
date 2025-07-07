{{-- 親レイアウトとして layouts.app を継承 --}}
@extends('layouts.app')

@section('content')
    {{-- 認証系ページに中央寄せや背景色を適用するラッパー --}}
    <div class="bg-gray-100 flex items-center justify-center min-h-screen">
        @yield('guest_content') {{-- 認証系ビューのコンテンツをここに挿入 --}}
    </div>
@endsection
