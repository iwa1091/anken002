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
                                <th class="table-header">日付</th>
                                <th class="table-header">申請種別</th>
                                <th class="table-header">ステータス</th>
                                <th class="table-header">詳細</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pendingApplications as $application)
                                <tr class="table-row">
                                    {{-- 勤怠の日付を表示 --}}
                                    <td class="table-data">{{ $application->attendance->full_formatted_date ?? 'N/A' }}</td>
                                    <td class="table-data">勤怠修正申請</td> {{-- 常に勤怠修正申請と仮定 --}}
                                    <td class="table-data status-pending">承認待ち</td>
                                    <td class="table-data">
                                        {{-- 詳細ボタン (FN033) --}}
                                        {{-- 承認待ち申請の詳細画面では修正不可となるよう、パラメータを渡す --}}
                                        <a href="{{ route('attendance.detail', ['id' => $application->attendance_id, 'from_application_list' => true]) }}" class="detail-button">詳細</a>
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
                                <th class="table-header">日付</th>
                                <th class="table-header">申請種別</th>
                                <th class="table-header">ステータス</th>
                                <th class="table-header">詳細</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($approvedApplications as $application)
                                <tr class="table-row">
                                    {{-- 勤怠の日付を表示 --}}
                                    <td class="table-data">{{ $application->attendance->full_formatted_date ?? 'N/A' }}</td>
                                    <td class="table-data">勤怠修正申請</td> {{-- 常に勤怠修正申請と仮定 --}}
                                    <td class="table-data status-approved">承認済み</td>
                                    <td class="table-data">
                                        {{-- 詳細ボタン (FN033) --}}
                                        <a href="{{ route('attendance.detail', ['id' => $application->attendance_id]) }}" class="detail-button">詳細</a>
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
