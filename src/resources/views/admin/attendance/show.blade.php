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
        {{-- 全てのバリデーションエラーを表示するブロック --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <p class="error-message">入力内容に問題があります。以下のエラーをご確認ください:</p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="detail-section">
            <div class="info-group">
                <span class="info-label">名前</span>
                <span class="info-value">{{ $attendance->user->name ?? 'N/A' }}</span>
            </div>
            <div class="info-group">
                <span class="info-label">日付</span>
                <span class="info-value">
                    <span class="date-year">{{ $attendance->formatted_year }}</span>
                    <span class="date-month-day">{{ $attendance->formatted_month_day }}</span>
                </span>
            </div>

            {{-- 管理者による直接編集フォーム --}}
            <form action="{{ route('admin.attendance.update', $attendance->id) }}" method="POST" class="attendance-edit-form" novalidate>
                @csrf
                @method('PUT') {{-- PUTメソッドを使用 --}}

                {{-- 出勤・退勤時間 --}}
                <div class="form-group">
                    <label for="check_in_time" class="form-label">出勤・退勤</label>
                    <input type="time" id="check_in_time" name="check_in_time"
                            value="{{ old('check_in_time', $attendance->formatted_check_in_time) }}">
                    <span class="mx-2">〜</span>
                    <input type="time" id="check_out_time" name="check_out_time"
                            value="{{ old('check_out_time', $attendance->formatted_check_out_time) }}">
                    @error('check_in_time')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                    @error('check_out_time')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 休憩時間 --}}
                <div class="form-group">
                    <label class="form-label">休憩</label>
                    <div id="breaks-container" class="breaks-container">
                        {{-- 既存の休憩レコードを表示 --}}
                        @forelse ($attendance->breakTimes as $index => $break)
                            <div class="break-item">
                                <span class="break-label">
                                    @if ($index === 0)
                                        休憩
                                    @else
                                        休憩{{ $index + 1 }}
                                    @endif
                                </span>
                                <input type="time" id="breaks_{{ $index }}_start" name="breaks[{{ $index }}][start]"
                                        value="{{ old("breaks.{$index}.start", $break->formatted_start_time) }}">
                                <span class="mx-2">〜</span>
                                <input type="time" id="breaks_{{ $index }}_end" name="breaks[{{ $index }}][end]"
                                        value="{{ old("breaks." . $index . ".end", $break->formatted_end_time) }}">
                            </div>
                            @error("breaks.{$index}.start")
                                <p class="error-message">{{ $message }}</p>
                            @enderror
                            @error("breaks.{$index}.end")
                                <p class="error-message">{{ $message }}</p>
                            @enderror
                        @empty
                            {{-- 休憩レコードがない場合は、以下の新しい休憩フィールドが最初の休憩として扱われる --}}
                        @endforelse

                        {{-- 新しい休憩を追加するための空の入力フィールド --}}
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
                            <input type="time" id="breaks_{{ $breakCount }}_start" name="breaks[{{ $breakCount }}][start]"
                                    value="{{ old("breaks." . $breakCount . ".start") }}">
                            <span class="mx-2">〜</span>
                            <input type="time" id="breaks_{{ $breakCount }}_end" name="breaks[{{ $breakCount }}][end]"
                                    value="{{ old("breaks." . $breakCount . ".end") }}">
                        </div>
                    </div>
                    @error('breaks')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 備考 --}}
                <div class="form-group">
                    <label for="remarks" class="form-label">備考</label>
                    <textarea id="remarks" name="remarks" rows="4">{{ old('remarks', $attendance->remarks) }}</textarea>
                    @error('remarks')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 修正ボタン --}}
                <button type="submit" class="submit-button">修正</button>
            </form>
        </div>
    @endif
</div>
@endsection
