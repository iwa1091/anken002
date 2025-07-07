{{-- layouts/app.blade.php を継承 --}}
@extends('layouts.app')

{{-- ページのタイトルを設定 --}}
@section('title', '勤怠一覧')

{{-- ページ固有のCSSを読み込む --}}
@section('css')
<link rel="stylesheet" href="{{ asset('css/list.css') }}">
@endsection

@section('content')
<div class="attendance-list__container">
    {{-- FN023: 勤怠一覧画面のタイトル表示 --}}
    <h2 class="attendance-list__page-title">勤怠一覧</h2>

    <div class="attendance-list__header">
        <div class="attendance-list__month-nav">
            {{-- FN024: 「前月」を押下した時に，表示月の前月の情報が表示される --}}
            {{-- $prevMonth はコントローラから渡されるCarbonインスタンスを想定 --}}
            <a href="{{ route('attendance.list', ['month' => $prevMonth->format('Y-m')]) }}" class="month-nav-link">&lt;－前月</a>

            {{-- FN024: 遷移した際に現在の月が表示される --}}
            {{-- $currentMonth はコントローラから渡されるCarbonインスタンスを想定 --}}
            <span class="attendance-list__current-month">
                <img src="{{ asset('images/image 1.svg') }}" alt="Calendar Icon" class="month-icon">
                {{ $currentMonth->format('Y年m月') }}
            </span>

            {{-- FN024: 「翌月」を押下した時に，表示月の翌月の情報が表示される --}}
            {{-- $nextMonth はコントローラから渡されるCarbonインスタンスを想定 --}}
            <a href="{{ route('attendance.list', ['month' => $nextMonth->format('Y-m')]) }}" class="month-nav-link">翌月－&gt;</a>
        </div>
    </div>

    <div class="attendance-list__table-wrapper">
        <table class="attendance-list__table">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th> {{-- FN025: 詳細遷移機能 --}}
                </tr>
            </thead>
            <tbody>
                {{-- FN023: 自分が行った勤怠情報が全て表示されていること --}}
                {{-- $attendances はコントローラから渡される勤怠レコードのコレクションを想定 --}}
                @forelse ($attendances as $attendance)
                <tr>
                    {{-- 日付の表示をアクセサから取得 --}}
                    <td>{{ $attendance->formatted_date }}</td>
                    {{-- 出勤時刻の表示（モデルのアクセサを使用） --}}
                    <td>{{ $attendance->formatted_check_in_time }}</td>
                    {{-- 退勤時刻の表示（モデルのアクセサを使用） --}}
                    <td>{{ $attendance->formatted_check_out_time }}</td>
                    {{-- 休憩時間の表示（モデルのアクセサを使用） --}}
                    <td>{{ $attendance->formatted_break_time }}</td>
                    {{-- 合計勤務時間の表示（モデルのアクセサを使用） --}}
                    <td>{{ $attendance->formatted_working_time }}</td>
                    <td>
                        {{-- FN025: 「詳細」を押下すると，その日の勤怠詳細画面に遷移すること --}}
                        {{-- attendance.show ルートに勤怠IDをパラメータとして渡す --}}
                        <a href="{{ route('attendance.show', ['id' => $attendance->id]) }}" class="attendance-list__detail-button">詳細</a>
                    </td>
                </tr>
                @empty
                {{-- 勤怠情報がない場合のメッセージ --}}
                <tr>
                    <td colspan="6">
                        {{ $currentMonth->format('Y年m月') }}の勤怠情報はありません。
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
