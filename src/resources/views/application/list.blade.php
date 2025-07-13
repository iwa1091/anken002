{{-- layouts/app.blade.php を継承 --}}
@extends('layouts.app')

{{-- ページタイトルを設定 --}}
@section('title', '申請一覧')

{{-- ページ固有のCSSを読み込む --}}
@section('css')
<link rel="stylesheet" href="{{ asset('css/app-list.css') }}">
@endsection

{{-- メインコンテンツ --}}
@section('content')
<div class="application-list-container">
    <h2 class="application-list-title">申請一覧</h2>

    <div class="tab-wrapper">
        {{-- 承認待ちタブのラジオボタンとラベル --}}
        <input type="radio" id="tab-pending" name="application-tabs" class="tab-input" checked>
        <label for="tab-pending" class="tab-label">承認待ち</label>

        {{-- 承認済みタブのラジオボタンとラベル --}}
        <input type="radio" id="tab-approved" name="application-tabs" class="tab-input">
        <label for="tab-approved" class="tab-label">承認済み</label>

        {{-- 承認待ちの申請一覧 (FN031) --}}
        <div id="content-pending" class="tab-content">
            <h3 class="tab-content-heading">承認待ちの申請</h3>
            @if ($pendingApplications->isEmpty())
                <p class="no-applications-message">承認待ちの申請はありません。</p>
            @else
                <div class="application-table-wrapper">
                    <table class="application-table">
                        <thead>
                            <tr>
                                <th class="table-header">状態</th>
                                <th class="table-header">名前</th>
                                <th class="table-header">対象日時</th>
                                <th class="table-header">申請理由</th>
                                <th class="table-header">申請日時</th> {{-- 申請日時ヘッダー --}}
                                <th class="table-header">詳細</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pendingApplications as $application)
                                <tr class="table-row">
                                    <td class="table-data status-pending">承認待ち</td>
                                    {{-- ユーザー名が存在しない場合は空欄にする --}}
                                    <td class="table-data">{{ $application->user->name ?? '' }}</td>
                                    {{-- 勤怠の日付が存在しない場合は空欄にする --}}
                                    <td class="table-data">{{ $application->attendance->full_formatted_date ?? '' }}</td>
                                    {{-- 申請理由が存在しない場合は空欄にする --}}
                                    <td class="table-data">{{ $application->reason ?? '' }}</td>
                                    {{-- 申請日時を表示 (アクセサを使用) --}}
                                    <td class="table-data">{{ $application->formatted_created_at ?? '' }}</td>
                                    <td class="table-data">
                                        {{-- 詳細ボタン (FN033) --}}
                                        {{-- 承認待ち申請の詳細画面では修正不可となるよう、パラメータを渡す --}}
                                        <a href="{{ route('attendance.show', ['id' => $application->attendance_id, 'from_application_list' => true]) }}" class="detail-button">詳細</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- 承認済みの申請一覧 (FN032) --}}
        <div id="content-approved" class="tab-content">
            <h3 class="tab-content-heading">承認済みの申請</h3>
            @if ($approvedApplications->isEmpty())
                <p class="no-applications-message">承認済みの申請はありません。</p>
            @else
                <div class="application-table-wrapper">
                    <table class="application-table">
                        <thead>
                            <tr>
                                <th class="table-header">状態</th>
                                <th class="table-header">名前</th>
                                <th class="table-header">対象日時</th>
                                <th class="table-header">申請理由</th>
                                <th class="table-header">申請日時</th> {{-- 申請日時ヘッダー --}}
                                <th class="table-header">詳細</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($approvedApplications as $application)
                                <tr class="table-row">
                                    <td class="table-data status-approved">承認済み</td>
                                    {{-- ユーザー名が存在しない場合は空欄にする --}}
                                    <td class="table-data">{{ $application->user->name ?? '' }}</td>
                                    {{-- 勤怠の日付が存在しない場合は空欄にする --}}
                                    <td class="table-data">{{ $application->attendance->full_formatted_date ?? '' }}</td>
                                    {{-- 申請理由が存在しない場合は空欄にする --}}
                                    <td class="table-data">{{ $application->reason ?? '' }}</td>
                                    {{-- 申請日時を表示 (アクセサを使用) --}}
                                    <td class="table-data">{{ $application->formatted_created_at ?? '' }}</td>
                                    <td class="table-data">
                                        {{-- 詳細ボタン (FN033) --}}
                                        <a href="{{ route('attendance.show', ['id' => $application->attendance_id]) }}" class="detail-button">詳細</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
