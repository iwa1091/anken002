{{-- layouts/app.blade.php を継承 --}}
@extends('layouts.app')

{{-- ページタイトルを設定 --}}
@section('title', '勤怠詳細')

{{-- ページ固有のCSSを読み込む --}}
@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection


{{-- メインコンテンツ --}}
@section('content')
<div class="detail-container">
    <h2>勤怠詳細</h2>

    {{-- 勤怠情報が存在しない場合のエラーハンドリング --}}
    @if (!isset($attendance) || !$attendance)
        <p>指定された勤怠情報が見つかりませんでした。</p>
    @else
        {{-- ユーザー名と日付 (FN026) --}}
        <div class="info-header">
            <span class="info-label">名前</span>
            <span class="info-value">{{ $attendance->user->name ?? 'N/A' }}</span>
        </div>
        <div class="info-header">
            {{-- 日付の表示をAttendanceモデルのアクセサから取得し、年と月日を横並びに表示 --}}
            <span class="info-label">日付</span>
            <span class="info-value">
                <span class="date-year">{{ $attendance->formatted_year }}</span>
                <span class="date-month-day">{{ $attendance->formatted_month_day }}</span>
            </span>
        </div>

        {{-- 修正申請の状態に応じて表示を切り替える (FN033) --}}
        {{-- $attendance->hasPendingCorrectionRequest は、Attendanceモデルに実装する想定のプロパティまたはメソッドです。 --}}
        @if ($attendance->hasPendingCorrectionRequest)
            {{-- 承認待ちの場合に表示する情報 (出勤・退勤) --}}
            <div class="info-group">
                <span class="info-label">出勤・退勤</span>
                <span class="info-value">{{ $attendance->formatted_check_in_time }}</span>
                <span class="mx-2">〜</span>
                <span class="info-value">{{ $attendance->formatted_check_out_time }}</span>
            </div>
            {{-- 承認待ちの場合に表示する休憩時間 - BreakTimeモデルから取得し、データがなくても表示 --}}
            <div class="info-group">
                <span class="info-label">休憩</span>
                <div class="info-breaks-display">
                    @forelse ($attendance->breakTimes as $break)
                        <span class="info-value">{{ $break->formatted_start_time }} 〜 {{ $break->formatted_end_time }}</span><br>
                    @empty
                        <span class="info-value">00:00 〜 00:00</span>
                    @endforelse
                </div>
            </div>
            <div class="info-group">
                <span class="info-label">備考</span>
                <span class="info-value">{{ $attendance->remarks ?? '' }}</span>
            </div>
            <div class="pending-message">
                <p class="error-message">*承認待ちのため修正はできません。</p>
            </div>
        @else
            {{-- コントローラからフラッシュされたアプリケーションエラーメッセージを表示 --}}
            {{-- 'application_error' というキーのエラーが存在する場合に表示 --}}
            @if ($errors->has('application_error'))
                <div class="alert alert-danger">
                    <p class="error-message">{{ $errors->first('application_error') }}</p>
                </div>
            @endif

            {{-- ★追加: 全てのバリデーションエラーを表示するブロック ★ --}}
            @if ($errors->any() && !$errors->has('application_error'))
                <div class="alert alert-danger">
                    <p class="error-message">入力内容に問題があります。以下のエラーをご確認ください:</p>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            {{-- ★追加ここまで ★ --}}

            {{-- 修正申請フォーム (FN027, FN030) --}}
            {{-- action属性を新しいルートに変更し、attendance_idを渡すように修正 --}}
            <form action="{{ route('application.storeCorrectionRequest', ['attendance_id' => $attendance->id]) }}" method="POST" class="correction-form" novalidate>
                @csrf

                {{-- 出勤・退勤時間 (FN026, FN027) --}}
                <div class="form-group">
                    <label for="requested_check_in_time" class="form-label">出勤・退勤</label>
                    <input type="time" id="requested_check_in_time" name="requested_check_in_time"
                            value="{{ old('requested_check_in_time', $attendance->formatted_check_in_time) }}">
                    <span class="mx-2">〜</span>
                    <input type="time" id="requested_check_out_time" name="requested_check_out_time"
                            value="{{ old('requested_check_out_time', $attendance->formatted_check_out_time) }}">
                    @error('requested_check_in_time')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                    @error('requested_check_out_time')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 休憩時間 (FN026, FN027) --}}
                <div class="form-group">
                    <label class="form-label">休憩</label>
                    <div id="breaks-container" class="breaks-container">
                        {{-- 既存の休憩レコードを表示 --}}
                        @forelse ($attendance->breakTimes as $index => $break)
                            <div class="break-item">
                                {{-- 最初の休憩は「休憩」、それ以降は「休憩2」「休憩3」と表示 --}}
                                <span class="break-label">
                                    @if ($index === 0)
                                        休憩
                                    @else
                                        休憩{{ $index + 1 }}
                                    @endif
                                </span>
                                <input type="time" id="requested_breaks_{{ $index }}_start" name="requested_breaks[{{ $index }}][start]"
                                        value="{{ old("requested_breaks.{$index}.start", $break->formatted_start_time) }}">
                                <span class="mx-2">〜</span>
                                <input type="time" id="requested_breaks_{{ $index }}_end" name="requested_breaks[{{ $index }}][end]"
                                        value="{{ old("requested_breaks." . $index . ".end", $break->formatted_end_time) }}">
                            </div>
                            @error("requested_breaks.{$index}.start")
                                <p class="error-message">{{ $message }}</p>
                            @enderror
                            @error("requested_breaks.{$index}.end")
                                <p class="error-message">{{ $message }}</p>
                            @enderror
                        @empty
                            {{-- 休憩レコードがない場合は、以下の新しい休憩フィールドが最初の休憩として扱われるため、このemptyブロックは不要 --}}
                        @endforelse

                        {{-- 新しい休憩を追加するための空の入力フィールド (FN026) --}}
                        {{-- $attendance->breakTimes のカウントを安全に取得 --}}
                        @php
                            $breakCount = (isset($attendance->breakTimes) && is_iterable($attendance->breakTimes)) ? count($attendance->breakTimes) : 0;
                        @endphp
                        <div class="break-item" id="new-break-template">
                            {{-- 既存の休憩がない場合は「休憩」、ある場合は次の連番を表示 --}}
                            <span class="break-label">
                                @if ($breakCount === 0)
                                    休憩
                                @else
                                    休憩{{ $breakCount + 1 }}
                                @endif
                            </span>
                            <input type="time" id="requested_breaks_{{ $breakCount }}_start" name="requested_breaks[{{ $breakCount }}][start]"
                                    value="{{ old("requested_breaks." . $breakCount . ".start") }}">
                            <span class="mx-2">〜</span>
                            <input type="time" id="requested_breaks_{{ $breakCount }}_end" name="requested_breaks[{{ $breakCount }}][end]"
                                    value="{{ old("requested_breaks." . $breakCount . ".end") }}">
                        </div>
                    </div>
                    {{-- 休憩時間に関するエラーメッセージ (FN029) --}}
                    @error('requested_breaks')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 備考 (FN027) --}}
                <div class="form-group">
                    <label for="reason" class="form-label">備考</label>
                    <textarea id="reason" name="reason" rows="4">{{ old('reason', $attendance->remarks) }}</textarea>
                    @error('reason')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 修正ボタン (FN030) --}}
                <button type="submit" class="submit-button">修正</button>
            </form>
        @endif
    @endif
</div>
@endsection
