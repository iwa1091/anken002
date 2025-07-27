@extends('layouts.admin-app') {{-- 管理者用の共通レイアウトを継承 --}}

@section('title', '勤怠詳細') {{-- ページタイトルを「勤怠詳細」に設定 --}}

@section('css')
    {{-- カスタムCSSファイルを読み込み --}}
    <link href="{{ asset('css/app-approve.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container">
    <h2 class="page-title">勤怠詳細</h2> {{-- ページタイトルを「勤怠詳細」に設定 --}}

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

    @if (!isset($correctionRequest) || !$correctionRequest)
        <p class="no-data">指定された修正申請が見つかりませんでした。</p>
    @else
        <div class="approval-details">
            <div class="detail-section user-info">
                <div class="info-group">
                    <span class="info-label">名前</span>
                    <span class="info-value">{{ $correctionRequest->user->name ?? 'N/A' }}</span>
                </div>
                <div class="info-group">
                    <span class="info-label">日付</span>
                    <span class="info-value">
                        <span class="date-year">{{ $correctionRequest->formatted_attendance_year }}</span>
                        <span class="date-month-day">{{ $correctionRequest->formatted_attendance_month_day }}</span>
                    </span>
                </div>
                {{-- 修正希望データの内容をここに統合 --}}
                <div class="info-group">
                    <span class="info-label">出勤・退勤</span>
                    <span class="info-value">
                        {{ $correctionRequest->formatted_requested_check_in_time }} 〜 {{ $correctionRequest->formatted_requested_check_out_time }}
                    </span>
                </div>
                <div class="info-group">
                    {{-- ★修正: 空のinfo-labelを削除しました。CSSでマージンを調整します。 ★ --}}
                    <div class="info-breaks-display">
                        @php
                            $requestedBreaks = is_array($correctionRequest->requested_breaks) ? $correctionRequest->requested_breaks : [];
                        @endphp
                        @forelse ($requestedBreaks as $index => $break)
                            <div class="break-item-display">
                                <span class="break-label">
                                    @if ($index === 0)
                                        休憩
                                    @else
                                        休憩{{ $index + 1 }}
                                    @endif
                                </span>
                                <span class="info-value">{{ $break['start'] ?? '00:00' }} 〜 {{ $break['end'] ?? '00:00' }}</span>
                            </div>
                        @empty
                            <div class="break-item-display">
                                <span class="break-label">休憩</span>
                                <span class="info-value">00:00 〜 00:00</span>
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="info-group">
                    <span class="info-label">修正理由</span>
                    <span class="info-value">{{ $correctionRequest->reason ?? 'なし' }}</span>
                </div>
            </div>

            {{-- 承認/却下フォーム (FN051) --}}
            @if ($correctionRequest->status === 'pending')
                <form action="{{ route('admin.stamp_correction_request.approve', $correctionRequest->id) }}" method="POST" class="approval-form">
                    @csrf

                    @error('status')
                        <p class="error-message">{{ $message }}</p>
                    @enderror

                    <div class="button-group">
                        <button type="submit" name="status" value="approved" class="approve-button">承認</button>
                    </div>
                </form>
            @else
                <p class="status-message">{{ $correctionRequest->status === 'approved' ? '承認済み' : '却下済み' }}</p>
            @endif
        </div>
    @endif
</div>
@endsection
