@extends('layouts.admin-app') {{-- 管理者用の共通レイアウトを継承 --}}

@section('title', '勤怠一覧') {{-- ページタイトルを設定 --}}

@section('css')
    {{-- カスタムCSSファイルを読み込み --}}
    <link href="{{ asset('css/admin-list.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="container">
        <h2 class="page-title">勤怠一覧</h2>

        {{-- 検索・フィルターフォーム --}}
        <div class="search-filter-section">
            <form action="{{ route('admin.attendance.list') }}" method="GET" class="search-form">
                <div class="form-group">
                    <label for="user_name">ユーザー名:</label>
                    <input type="text" id="user_name" name="user_name" value="{{ request('user_name') }}" placeholder="ユーザー名を入力">
                </div>
                <div class="form-group">
                    <label for="start_date">開始日:</label>
                    <input type="date" id="start_date" name="start_date" value="{{ request('start_date') }}">
                </div>
                <div class="form-group">
                    <label for="end_date">終了日:</label>
                    <input type="date" id="end_date" name="end_date" value="{{ request('end_date') }}">
                </div>
                <button type="submit" class="search-button">検索</button>
                <a href="{{ route('admin.attendance.list') }}" class="clear-button">クリア</a>
            </form>
        </div>

        {{-- 勤怠データテーブル --}}
        <div class="table-responsive">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ユーザー名</th>
                        <th>日付</th>
                        <th>出勤時刻</th>
                        <th>退勤時刻</th>
                        <th>休憩時間</th>
                        <th>勤務時間</th>
                        <th>ステータス</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- ここに勤怠データをループで表示します --}}
                    {{-- 例: $attendances はコントローラから渡される勤怠データのコレクション --}}
                    @forelse ($attendances as $attendance)
                        <tr>
                            <td>{{ $attendance->id }}</td>
                            <td>{{ $attendance->user->name ?? 'N/A' }}</td> {{-- ユーザー名を表示 (リレーションを想定) --}}
                            <td>{{ \Carbon\Carbon::parse($attendance->work_date)->format('Y/m/d') }}</td>
                            <td>{{ $attendance->start_time ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : '-' }}</td>
                            <td>{{ $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '-' }}</td>
                            <td>{{ $attendance->break_time ?? '-' }}</td> {{-- 休憩時間 (例: HH:MM形式) --}}
                            <td>{{ $attendance->work_time ?? '-' }}</td> {{-- 勤務時間 (例: HH:MM形式) --}}
                            <td>
                                @if ($attendance->status == 'approved')
                                    <span class="status-approved">承認済み</span>
                                @elseif ($attendance->status == 'pending')
                                    <span class="status-pending">承認待ち</span>
                                @else
                                    <span class="status-rejected">却下</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.attendance.edit', $attendance->id) }}" class="action-button edit-button">編集</a>
                                <form action="{{ route('admin.attendance.delete', $attendance->id) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-button delete-button" onclick="return confirm('本当に削除しますか？');">削除</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="no-data">勤怠データがありません。</td>
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
