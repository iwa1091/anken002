@extends('layouts.admin-app')

@section('title', '勤怠詳細')

@section('css')
    {{-- カスタムCSSファイルを読み込み --}}
    <link href="{{ asset('css/app-approve.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="background-container">
    <div class="detail-container">
        <h2>勤怠詳細</h2>

        @if (!isset($correctionRequest) || !$correctionRequest)
            <p class="no-data">指定された修正申請が見つかりませんでした。</p>
        @else
            <div class="background-white-section">
                {{-- 名前 --}}
                <div class="grid-row">
                    <span class="grid-label">名前</span>
                    <span class="grid-name">{{ $correctionRequest->user->name ?? 'N/A' }}</span>
                </div>

                {{-- 日付 --}}
                <div class="grid-row">
                    <span class="grid-label">日付</span>
                    <span class="grid-date">
                        <span class="date-year">{{ $correctionRequest->formatted_attendance_year }}</span>
                        <span class="date-month-day">{{ $correctionRequest->formatted_attendance_month_day }}</span>
                    </span>
                </div>

                {{-- 出勤・退勤 --}}
                <div class="grid-row">
                    <span class="grid-label">出勤・退勤</span>
                    <span class="grid-value">
                        {{ $correctionRequest->formatted_requested_check_in_time }}<span class="mx-2">~</span>{{ $correctionRequest->formatted_requested_check_out_time }}
                    </span>
                    <div></div>
                </div>

                {{-- 休憩時間 --}}
                <div class="info-breaks-display">
                    @php
                        $requestedBreaks = json_decode($correctionRequest->requested_breaks, true) ?? [];
                    @endphp


                    @forelse ($requestedBreaks as $index => $break)
                        <div class="grid-row break-item-display">
                            <span class="grid-label">
                                @if ($index === 0)
                                    休憩
                                @else
                                    休憩{{ $index + 1 }}
                                @endif
                            </span>
                            <span class="grid-value">
                                {{ $break['start'] ?? '' }}
                                @if (!empty($break['start']) && !empty($break['end']))
                                    <span class="mx-2">~</span>
                                @endif
                                {{ $break['end'] ?? '' }}
                            </span>
                            <div></div>
                        </div>
                    @empty
                        <div class="grid-row break-item-display">
                            <span class="grid-label">休憩</span>
                            <span class="grid-value">なし</span>
                            <div></div>
                        </div>
                    @endforelse
                </div>

                {{-- 備考 --}}
                <div class="grid-remarks">
                    <span class="grid-label">備考</span>
                    <span class="grid-value__remarks">{{ $correctionRequest->reason ?? 'なし' }}</span>
                    <div></div>
                </div>
            </div>

            {{-- 承認/却下フォーム --}}
            @if ($correctionRequest->status === 'pending')
                <div class="submit-button">
                    <form action="{{ route('admin.stamp_correction_request.approve', $correctionRequest->id) }}" method="POST" class="approval-form">
                        @csrf
                        @error('status')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                        <button type="submit" name="status" value="approved" class="approve-button">承認</button>
                    </form>
                </div>
            @else
                <p class="status-message pending-message">{{ $correctionRequest->status === 'approved' ? '承認済み' : '却下済み' }}</p>
            @endif
        @endif
    </div>
</div>
@endsection
