@extends('layouts.admin-app') {{-- 管理者用の共通レイアウトを継承 --}}

@section('title', '勤怠詳細') {{-- ページタイトルを「勤怠詳細」に設定 --}}

@section('css')
    {{-- カスタムCSSファイルを読み込み --}}
    <link href="{{ asset('css/admin-app-show.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container">
    <h2 class="page-title">勤怠詳細</h2> {{-- ページタイトルを「勤怠詳細」に設定 --}}

    @if (!isset($attendance) || !$attendance)
        <p class="no-data">指定された勤怠情報が見つかりませんでした。</p>
    @else
        <div class="detail-section">
            <div class="info-group">
                <span class="info-label">名前</span>
                <span class="info-name">{{ $attendance->user->name ?? 'N/A' }}</span>
            </div>
            <div class="info-group">
                <span class="info-label">日付</span>
                <span class="info-value">
                    <span class="date-year">{{ $attendance->formatted_year }}</span>
                    <span class="date-month-day">{{ $attendance->formatted_month_day }}</span>
                </span>
            </div>

            {{-- 管理者による直接編集フォーム --}}
            <form id="correctionForm" action="{{ route('admin.attendance.update', $attendance->id) }}" method="POST" class="attendance-edit-form" novalidate>
                @csrf
                @method('PUT') {{-- PUTメソッドを使用 --}}

                {{-- 出勤・退勤時間 --}}
                <div class="form-group">
                    <label for="check_in_time" class="form-label__time">出勤・退勤</label>
                    <div class="time-inputs">
                        <div class="error-container">
                            <input type="time" id="check_in_time" name="check_in_time"
                                value="{{ old('check_in_time', $attendance->formatted_check_in_time) }}">
                            @error('check_in_time')
                                <p class="error-message">{{ $message }}</p>
                            @enderror
                        </div>

                        <span class="mx-2">〜</span>

                        <div class="error-container">
                            <input type="time" id="check_out_time" name="check_out_time"
                                value="{{ old('check_out_time', $attendance->formatted_check_out_time) }}">
                            @error('check_out_time')
                                <p class="error-message">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- 休憩時間 --}}
                <div class="form-group">
                    <div id="breaks-container" class="breaks-container">
                        @forelse ($attendance->breakTimes as $index => $break)
                            <div class="break-item">
                                <span class="break-label">
                                    @if ($index === 0)
                                        休憩
                                    @else
                                        休憩{{ $index + 1 }}
                                    @endif
                                </span>

                                <div class="error-container">
                                    <input type="time" id="breaks_{{ $index }}_start" name="breaks[{{ $index }}][start]"
                                        value="{{ old("breaks.{$index}.start", $break->formatted_start_time) }}">
                                    <p class="error-message">@error("breaks.{$index}.start"){{ $message }}@enderror</p>
                                </div>

                                <span class="mx-2">〜</span>

                                <div class="error-container">
                                    <input type="time" id="breaks_{{ $index }}_end" name="breaks[{{ $index }}][end]"
                                        value="{{ old("breaks.{$index}.end", $break->formatted_end_time) }}">
                                    <p class="error-message">@error("breaks.{$index}.end"){{ $message }}@enderror</p>
                                </div>
                            </div>
                        @empty
                            {{-- 休憩レコードがない場合 --}}
                        @endforelse

                        {{-- 新しい休憩フィールド --}}
                        @php
                            $breakCount = (isset($attendance->breakTimes) && is_iterable($attendance->breakTimes)) ? count($attendance->breakTimes) : 0;
                        @endphp
                        <div class="break-item" id="new-break-template">
                            <span class="break-label">
                                @if ($breakCount === 0)
                                    休憩
                                @else
                                    休憩{{ $breakCount + 1 }}
                                @endif
                            </span>

                            <div class="error-container">
                                <input type="time" id="breaks_{{ $breakCount }}_start" name="breaks[{{ $breakCount }}][start]"
                                    value="{{ old("breaks." . $breakCount . ".start") }}">
                                <p class="error-message">@error("breaks.{$breakCount}.start"){{ $message }}@enderror</p>
                            </div>

                            <span class="mx-2">〜</span>

                            <div class="error-container">
                                <input type="time" id="breaks_{{ $breakCount }}_end" name="breaks[{{ $breakCount }}][end]"
                                    value="{{ old("breaks." . $breakCount . ".end") }}">
                                <p class="error-message">@error("breaks.{$breakCount}.end"){{ $message }}@enderror</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 備考 --}}
                <div class="form-group__remarks">
                    <label for="remarks" class="form-label__remarks">備考</label>
                    <div class="error-container">
                        <textarea id="remarks" name="remarks" rows="4">{{ old('remarks', $attendance->remarks) }}</textarea>
                        <p class="error-message">@error('remarks'){{ $message }}@enderror</p>
                    </div>
                </div>


            </form>
        </div>
        {{-- 修正ボタン --}}
        
        <button type="submit" class="submit-button" form="correctionForm">修正</button>
    @endif
</div>
@endsection
