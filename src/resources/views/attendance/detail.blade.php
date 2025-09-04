@extends('layouts.app')

@section('title', '勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection

@section('content')
<div class="detail-container background-container">
    <h2>勤怠詳細</h2>

    @if (!isset($attendance) || !$attendance)
        <p>指定された勤怠情報が見つかりませんでした。</p>
    @else

    {{-- ▼ ユーザー情報と日付情報 --}}
    <div class="background-white-section grid-section">
        <div class="grid-row">
            <span class="grid-label">名前</span>
            <span class="grid-name">{{ $attendance->user->name ?? 'N/A' }}</span>
        </div>
        <div class="grid-row">
            <span class="grid-label">日付</span>
            <span class="grid-data">
                <span class="date-year">{{ $attendance->formatted_year }}</span>
                <span class="date-month-day">{{ $attendance->formatted_month_day }}</span>
            </span>
        </div>
    </div>

    @if ($attendance->hasPendingCorrectionRequest)
        <div class="background-white-section grid-section">
            <div class="grid-row">
                <span class="grid-label">出勤・退勤</span>
                <div class="grid-value">
                    {{ $attendance->formatted_check_in_time }} <span class="mx-2">〜</span> {{ $attendance->formatted_check_out_time }}
                </div>
            </div>

            @forelse ($attendance->breakTimes as $index => $break)
                <div class="grid-row">
                    <span class="grid-label">
                        {{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}
                    </span>
                    <div class="grid-value">
                        {{ $break->formatted_start_time }} <span class="mx-2">〜</span> {{ $break->formatted_end_time }}
                    </div>
                </div>
            @empty
                <div class="grid-row">
                    <span class="grid-label">休憩</span>
                    <div class="grid-value">00:00 <span class="mx-2">〜</span> 00:00</div>
                </div>
            @endforelse

            <div class="grid-row">
                <span class="grid-label">備考</span>
                <span class="grid-value">{{ $attendance->remarks ?? '' }}</span>
            </div>
        </div>

        <div class="pending-message">
            <p class="error-message">*承認待ちのため修正はできません。</p>
        </div>

    @else
        <div class="background-white-section grid-section">

            <form id="correctionForm" action="{{ route('application.storeCorrectionRequest', ['attendance_id' => $attendance->id]) }}" method="POST" class="correction-form" novalidate>
                @csrf

                {{-- 出勤・退勤 --}}
                <div class="grid-row">
                    <label for="requested_check_in_time" class="grid-label">出勤・退勤</label>
                    <div class="grid-value time-inputs">
                        <div class="error-container">
                            <input type="time" id="requested_check_in_time" name="requested_check_in_time" value="{{ old('requested_check_in_time', $attendance->formatted_check_in_time) }}">
                            <p class="error-message">@error('requested_check_in_time'){{ $message }}@enderror</p>
                        </div>

                        <span class="mx-2">〜</span>

                        <div class="error-container">
                            <input type="time" id="requested_check_out_time" name="requested_check_out_time" value="{{ old('requested_check_out_time', $attendance->formatted_check_out_time) }}">
                            <p class="error-message">@error('requested_check_out_time'){{ $message }}@enderror</p>
                        </div>
                    </div>
                </div>

                {{-- 既存の休憩 --}}
                @forelse ($attendance->breakTimes as $index => $break)
                    <div class="break-item">
                        <div class="grid-row">
                            <label class="grid-label">{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</label>
                            <div class="grid-value time-inputs">
                                <div class="error-container">
                                    <input type="time" name="requested_breaks[{{ $index }}][start]" value="{{ old("requested_breaks.{$index}.start", $break->formatted_start_time) }}">
                                    <p class="error-message">@error("requested_breaks.{$index}.start"){{ $message }}@enderror</p>
                                </div>

                                <span class="mx-2">〜</span>

                                <div class="error-container">
                                    <input type="time" name="requested_breaks[{{ $index }}][end]" value="{{ old("requested_breaks.{$index}.end", $break->formatted_end_time) }}">
                                    <p class="error-message">@error("requested_breaks.{$index}.end"){{ $message }}@enderror</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    {{-- 空の場合は新しい休憩欄を表示 --}}
                @endforelse

                {{-- 新しい休憩 --}}
                @php $breakCount = isset($attendance->breakTimes) ? count($attendance->breakTimes) : 0; @endphp
                <div class="break-item">
                    <div class="grid-row">
                        <label class="grid-label">{{ $breakCount === 0 ? '休憩' : '休憩' . ($breakCount + 1) }}</label>
                        <div class="grid-value time-inputs">
                            <div class="error-container">
                                <input type="time" name="requested_breaks[{{ $breakCount }}][start]" value="{{ old("requested_breaks.{$breakCount}.start") }}">
                                <p class="error-message">@error("requested_breaks.{$breakCount}.start"){{ $message }}@enderror</p>
                            </div>

                            <span class="mx-2">〜</span>

                            <div class="error-container">
                                <input type="time" name="requested_breaks[{{ $breakCount }}][end]" value="{{ old("requested_breaks.{$breakCount}.end") }}">
                                <p class="error-message">@error("requested_breaks.{$breakCount}.end"){{ $message }}@enderror</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 備考 --}}
                <div class="grid-row">
                    <label for="reason" class="grid-label">備考</label>
                    <div class="grid-value">
                        <div class="error-container">
                            <textarea id="reason" name="reason" rows="4">{{ old('reason', $attendance->remarks) }}</textarea>
                            <p class="error-message">@error('reason'){{ $message }}@enderror</p>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <button type="submit" class="submit-button" form="correctionForm">修正</button>
    @endif
    @endif
</div>
@endsection
