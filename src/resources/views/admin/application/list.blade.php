@extends('layouts.admin-app') {{-- 管理者用の共通レイアウトを継承 --}}

@section('title', '申請一覧') {{-- ページタイトルを設定 --}}

@section('css')
    {{-- カスタムCSSファイルを読み込み --}}
    <link href="{{ asset('css/admin-app-list.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container">
    <h2 class="page-title">申請一覧</h2>

    {{-- Success/Error messages --}}
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="tabs">
        {{-- タブボタンをリンクに変更し、activeクラスを動的に設定 --}}
        <a href="{{ route('admin.stamp_correction_request.list', ['tab' => 'pending']) }}" class="tab-button {{ $activeTab === 'pending' ? 'active' : '' }}">承認待ち</a>
        <a href="{{ route('admin.stamp_correction_request.list', ['tab' => 'approved']) }}" class="tab-button {{ $activeTab === 'approved' ? 'active' : '' }}">承認済み</a>
    </div>

    {{-- タブコンテンツのactiveクラスを動的に設定 --}}
    <div id="pending" class="tab-content {{ $activeTab === 'pending' ? 'active' : '' }}">
        <div class="table-responsive">
            <table class="application-table">
                <thead>
                    <tr>
                        <th>状態</th>
                        <th>名前</th>
                        <th>対象日時</th>
                        <th>申請理由</th>
                        <th>申請日時</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        // コントローラから渡されたcorrectionRequestsコレクションをフィルタリングして承認待ちの申請を取得
                        $pendingRequests = $correctionRequests->filter(fn($req) => $req->status === 'pending');
                    @endphp
                    @forelse ($pendingRequests as $request)
                        <tr>
                            <td>承認待ち</td>
                            <td>{{ $request->user->name ?? 'N/A' }}</td>
                            {{-- 勤怠の日付はattendanceリレーションから取得 --}}
                            <td>{{ $request->attendance->full_formatted_date ?? 'N/A' }}</td>
                            <td>{{ $request->reason ?? '' }}</td>
                            {{-- ★修正: 申請日時を YYYY/MM/DD 形式に変更 ★ --}}
                            <td>{{ $request->created_at ? $request->created_at->format('Y/m/d') : 'N/A' }}</td>
                            <td>
                                {{-- FN049: 詳細画面への遷移 --}}
                                <a href="{{ route('admin.stamp_correction_request.approve.show', $request->id) }}" class="action-button detail-button">詳細</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="no-data">承認待ちの申請はありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="approved" class="tab-content {{ $activeTab === 'approved' ? 'active' : '' }}">
        <div class="table-responsive">
            <table class="application-table">
                <thead>
                    <tr>
                        <th>状態</th>
                        <th>名前</th>
                        <th>対象日時</th>
                        <th>申請理由</th>
                        <th>申請日時</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        // コントローラから渡されたcorrectionRequestsコレクションをフィルタリングして承認済みの申請を取得
                        $approvedRequests = $correctionRequests->filter(fn($req) => $req->status === 'approved');
                    @endphp
                    @forelse ($approvedRequests as $request)
                        <tr>
                            <td>承認済み</td>
                            <td>{{ $request->user->name ?? 'N/A' }}</td>
                            {{-- 勤怠の日付はattendanceリレーションから取得 --}}
                            <td>{{ $request->attendance->full_formatted_date ?? 'N/A' }}</td>
                            <td>{{ $request->reason ?? '' }}</td>
                            {{-- ★修正: 申請日時を YYYY/MM/DD 形式に変更 ★ --}}
                            <td>{{ $request->created_at ? $request->created_at->format('Y/m/d') : 'N/A' }}</td>
                            <td>
                                {{-- FN049: 詳細画面への遷移 --}}
                                <a href="{{ route('admin.stamp_correction_request.approve.show', $request->id) }}" class="action-button detail-button">詳細</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="no-data">承認済みの申請はありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
