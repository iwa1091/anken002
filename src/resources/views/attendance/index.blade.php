{{-- layouts/app.blade.php を継承 --}}
@extends('layouts.app')

{{-- ページタイトルを「勤怠」に設定 --}}
@section('title', '勤怠')

{{-- このページ固有のCSSを読み込む --}}
@section('css')
    <link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endsection

@section('content')
    <div class="attendance-container">

        <div class="status-display">
            {{-- FN019: ステータス確認機能 --}}
            <p>{{ $attendanceStatus }}</p>
        </div>

        <div class="date-display">
            {{-- FN018: 現在の日時情報取得機能 --}}
            {{-- 現在の日付を表示（例: 2025年07月01日） --}}
            <p class="current-date">{{ $currentDate->format('Y年m月d日') }}</p>
            {{-- 現在の日本時間を表示（例: 08:00） --}}
            <p class="current-time">{{ $currentTime->format('H:i') }}</p>
        </div>

        {{-- ステータスに応じたボタンの表示切り替え --}}
        @if ($attendanceStatus == '勤務外')
            {{-- 勤怠登録画面＿出勤前（一般ユーザー） --}}
            <div class="stamp-buttons single-button">
                <form action="{{ route('attendance.checkin') }}" method="POST" class="stamp-form">
                    @csrf
                    <button type="submit" class="stamp-button checkin-form">
                        出勤
                    </button>
                </form>
            </div>
        @elseif ($attendanceStatus == '出勤中')
            {{-- 勤怠登録画面＿出勤後（一般ユーザー） --}}
            <div class="stamp-buttons two-columns">
                <form action="{{ route('attendance.checkout') }}" method="POST" class="stamp-form">
                    @csrf
                    <button type="submit" class="stamp-button ">
                        退勤
                    </button>
                </form>
                <form action="{{ route('attendance.breakin') }}" method="POST" class="stamp-form">
                    @csrf
                    <button type="submit" class="stamp-button">
                        休憩入
                    </button>
                </form>
            </div>
        @elseif ($attendanceStatus == '休憩中')
            {{-- 勤怠登録画面＿休憩中（一般ユーザー） --}}
            <div class="stamp-buttons single-button">
                <form action="{{ route('attendance.breakout') }}" method="POST" class="stamp-form">
                    @csrf
                    <button type="submit" class="stamp-button">
                        休憩戻
                    </button>
                </form>
            </div>
        @elseif ($attendanceStatus == '退勤済')
            {{-- 勤怠登録画面＿退勤後（一般ユーザー） --}}
            <div class="message-after-checkout">
                <p>お疲れさまでした。</p>
            </div>
        @endif
    </div>
@endsection
