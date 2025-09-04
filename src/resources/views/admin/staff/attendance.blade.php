@extends('layouts.admin-app') {{-- 管理者用の共通レイアウトを継承 --}}

@section('title', 'スタッフ別勤怠一覧') {{-- ページタイトルを「スタッフ別勤怠一覧」に設定 --}}

@section('css')
    {{-- カスタムCSSファイルを読み込み --}}
    <link href="{{ asset('css/admin-staff-attendance.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container">
    {{-- ページタイトル (FN043) --}}
    <h2 class="page-title">{{ $staff->name ?? 'N/A' }} の勤怠一覧</h2>

    {{-- Success/Error messages --}}

    {{-- 月移動ナビゲーション (FN044) --}}
    <div class="month-navigation-section">
        <div class="month-nav">
            {{-- 「前月」へのリンク --}}
            <a href="{{ route('admin.staff.attendance', ['id' => $staff->id, 'month' => $previousMonth]) }}" class="month-nav-link">&lt;－前月</a>

            {{-- 現在の月表示 --}}
            <span class="current-month-display">
                {{-- カレンダーアイコンのSVGを直接埋め込むか、適切な画像パスを使用 --}}
                <img src="{{ asset('images/image 1.svg') }}" alt="Calendar Icon" class="month-icon">
                {{ $targetMonth->format('Y年m月') }}
            </span>

            {{-- 「翌月」へのリンク --}}
            <a href="{{ route('admin.staff.attendance', ['id' => $staff->id, 'month' => $nextMonth]) }}" class="month-nav-link">翌月－&gt;</a>
        </div>
    </div>

    {{-- 勤怠データテーブル (FN043) --}}
    <div class="table-responsive">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($attendances as $attendance)
                <tr>
                    <td data-label="日付">{{ $attendance->formatted_date }}</td>
                    <td data-label="出勤">{{ $attendance->formatted_check_in_time }}</td>
                    <td data-label="退勤">{{ $attendance->formatted_check_out_time }}</td>
                    <td data-label="休憩">{{ $attendance->formatted_break_time }}</td>
                    <td data-label="実労働時間">{{ $attendance->formatted_working_time }}</td>
                    <td data-label="詳細">
                        {{-- FN046: その日の勤怠詳細画面へ遷移 --}}
                        <a href="{{ route('admin.attendance.show', $attendance->id) }}" class="action-button detail-button">詳細</a>
                    </td>
                </tr>
                @empty
                    <tr>
                        <td colspan="7" class="no-data">この月の勤怠データがありません。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{-- CSV出力ボタン (FN045) --}}
        <div class="csv-export-button-container">
            <a href="{{ route('admin.staff.attendance.csv', ['id' => $staff->id, 'month' => $targetMonth->format('Y-m')]) }}" class="action-button csv-button">CSV出力</a>
        </div>
    </div>
</div>
@endsection
