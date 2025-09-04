@extends('layouts.admin-app') {{-- 管理者用の共通レイアウトを継承 --}}

@section('title', '勤怠一覧') {{-- ページタイトルを設定 --}}

@section('css')
    {{-- カスタムCSSファイルを読み込み --}}
    <link href="{{ asset('css/admin-list.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="container">
        {{-- 勤怠一覧のタイトルを「YYYY年M月D日の勤怠」形式で表示 --}}
        <h2 class="page-title">{{ $currentMonth->format('Y年n月j日') }} の勤怠</h2>

        {{-- 日移動ナビゲーション --}}
        {{-- コントローラから $currentMonth, $prevDay, $nextDay のCarbonインスタンスが渡されることを想定 --}}
        <div class="month-navigation-section">
            <div class="month-nav">
                {{-- 「前日」へのリンク --}}
                {{-- ルートパラメータを 'date' に変更し、$prevDay を使用 --}}
                <a href="{{ route('admin.attendance.list', ['date' => $prevDay->format('Y-m-d')]) }}" class="month-nav-link">&lt;－前日</a>

                {{-- 現在の日付表示を「YYYY/MM/DD」形式で表示 --}}
                <span class="current-month-display">
                    {{-- カレンダーアイコンのSVGを直接埋め込むか、適切な画像パスを使用 --}}
                    <img src="{{ asset('images/image 1.svg') }}" alt="Calendar Icon" class="month-icon">
                    {{ $currentMonth->format('Y/m/d') }}
                </span>

                {{-- 「翌日」へのリンク --}}
                {{-- ルートパラメータを 'date' に変更し、$nextDay を使用 --}}
                <a href="{{ route('admin.attendance.list', ['date' => $nextDay->format('Y-m-d')]) }}" class="month-nav-link">翌日－&gt;</a>
            </div>
        </div>

        {{-- 勤怠データテーブル --}}
        <div class="table-responsive">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>名前</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- ここに勤怠データをループで表示します --}}
                    {{-- 例: $attendances はコントローラから渡される勤怠データのコレクション --}}
                    @forelse ($attendances as $attendance)
                    {{-- list.blade.php の該当部分 --}}
                    <tr>
                        <td>{{ $attendance->user->name ?? 'N/A' }}</td>
                        <td>{{ $attendance->formatted_check_in_time }}</td> {{-- アクセサを使用 --}}
                        <td>{{ $attendance->formatted_check_out_time }}</td> {{-- アクセサを使用 --}}
                        <td>{{ $attendance->formatted_break_time }}</td>    {{-- アクセサを使用 --}}
                        <td>{{ $attendance->formatted_working_time }}</td>  {{-- アクセサを使用 --}}
                        <td>
                            <a href="{{ route('admin.attendance.show', $attendance->id) }}" class="action-button detail-button">詳細</a>
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="no-data">この月の勤怠データがありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ページネーションリンク --}}
        <div class="pagination-links">
            {{ $attendances->links() }}
        </div>
    </div>
@endsection
