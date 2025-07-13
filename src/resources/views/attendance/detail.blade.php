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
            {{-- 日付の表示をAttendanceモデルのアクセサから取得 --}}
            <span class="info-label">日付</span>
            <span class="info-value">{{ $attendance->full_formatted_date }}</span>
        </div>

        {{-- 修正申請の状態に応じて表示を切り替える (FN033) --}}
        {{-- $attendance->hasPendingCorrectionRequest は、Attendanceモデルに実装する想定のプロパティまたはメソッドです。 --}}
        @if ($attendance->hasPendingCorrectionRequest)
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

            {{-- 修正申請フォーム (FN027, FN030) --}}
            {{-- action属性を新しいルートに変更し、attendance_idを渡すように修正 --}}
            <form action="{{ route('application.storeCorrectionRequest', ['attendance_id' => $attendance->id]) }}" method="POST" class="correction-form">
                @csrf

                {{-- 出勤・退勤時間 (FN026, FN027) --}}
                {{-- name属性をrequested_check_in_timeに変更 --}}
                <div class="form-group">
                    <label for="requested_check_in_time" class="form-label">出勤時間</label>
                    <input type="time" id="requested_check_in_time" name="requested_check_in_time"
                            value="{{ old('requested_check_in_time', $attendance->formatted_check_in_time) }}">
                    @error('requested_check_in_time')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                {{-- name属性をrequested_check_out_timeに変更 --}}
                <div class="form-group">
                    <label for="requested_check_out_time" class="form-label">退勤時間</label>
                    <input type="time" id="requested_check_out_time" name="requested_check_out_time"
                            value="{{ old('requested_check_out_time', $attendance->formatted_check_out_time) }}">
                    @error('requested_check_out_out_time')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                    {{-- 出退勤時間の整合性エラーメッセージ (FN029) --}}
                    {{-- requested_check_in_timeのエラーもここで表示する --}}
                    @error('requested_check_in_time')
                        @if (str_contains($message, '不適切な値です') || str_contains($message, 'より後の時刻を入力してください'))
                            <p class="error-message">出勤時間もしくは退勤時間が不適切な値です</p>
                        @endif
                    @enderror
                    @error('requested_check_out_time')
                        @if (str_contains($message, '不適切な値です') || str_contains($message, 'より後の時刻を入力してください'))
                            <p class="error-message">出勤時間もしくは退勤時間が不適切な値です</p>
                        @endif
                    @enderror
                </div>

                {{-- 休憩時間 (FN026, FN027) --}}
                <div class="form-group">
                    <label class="form-label">休憩時間</label>
                    <div id="breaks-container" class="breaks-container">
                        {{-- 既存の休憩レコードを表示 --}}
                        {{-- $attendance->breaks が null でない、かつ反復可能であることを確認 --}}
                        @if (isset($attendance->breaks) && is_iterable($attendance->breaks))
                            @foreach($attendance->breaks as $index => $break)
                                <div class="break-item">
                                    {{-- name属性をrequested_breaks[{{ $index }}][start]に変更 --}}
                                    <input type="time" name="requested_breaks[{{ $index }}][start]"
                                            value="{{ old("requested_breaks.{$index}.start", \Carbon\Carbon::parse($break->start_time)->format('H:i')) }}">
                                    <span class="mx-2">〜</span>
                                    {{-- name属性をrequested_breaks[{{ $index }}][end]に変更 --}}
                                    <input type="time" name="requested_breaks[{{ $index }}][end]"
                                            value="{{ old("requested_breaks." . $index . ".end", \Carbon\Carbon::parse($break->end_time)->format('H:i')) }}">
                                </div>
                                {{-- エラーメッセージの表示箇所を修正 --}}
                                @error("requested_breaks.{$index}.start")
                                    <p class="error-message">{{ $message }}</p>
                                @enderror
                                @error("requested_breaks.{$index}.end")
                                    <p class="error-message">{{ $message }}</p>
                                @enderror
                            @endforeach
                        @endif

                        {{-- 新しい休憩を追加するための空の入力フィールド (FN026) --}}
                        {{-- $attendance->breaks のカウントを安全に取得 --}}
                        @php
                            $breakCount = (isset($attendance->breaks) && is_iterable($attendance->breaks)) ? count($attendance->breaks) : 0;
                        @endphp
                        <div class="break-item" id="new-break-template">
                            {{-- name属性をrequested_breaks[{{ $breakCount }}][start]に変更 --}}
                            <input type="time" name="requested_breaks[{{ $breakCount }}][start]"
                                    value="{{ old("requested_breaks." . $breakCount . ".start") }}">
                            <span class="mx-2">〜</span>
                            {{-- name属性をrequested_breaks[{{ $breakCount }}][end]に変更 --}}
                            <input type="time" name="requested_breaks[{{ $breakCount }}][end]"
                                    value="{{ old("requested_breaks." . $breakCount . ".end") }}">
                        </div>
                    </div>
                    {{-- 休憩時間に関するエラーメッセージ (FN029) --}}
                    @error('requested_breaks.*.start')
                        @if (str_contains($message, '勤務時間外です'))
                            <p class="error-message">休憩時間が勤務時間外です</p>
                        @endif
                    @enderror
                    @error('requested_breaks.*.end')
                        @if (str_contains($message, '勤務時間外です'))
                            <p class="error-message">休憩時間が勤務時間外です</p>
                        @endif
                    @enderror
                </div>

                {{-- 備考 (FN027) --}}
                <div class="form-group">
                    <label for="reason" class="form-label">修正理由</label>
                    <textarea id="reason" name="reason" rows="4">{{ old('reason', $attendance->remarks) }}</textarea>
                    @error('reason')
                        <p class="error-message">修正理由を記入してください</p>
                    @enderror
                </div>

                {{-- 修正ボタン (FN030) --}}
                <button type="submit" class="submit-button">修正</button>
            </form>
        @endif {{-- @if ($attendance->hasPendingCorrectionRequest) の閉じタグ --}}
    @endif
</div>
@endsection
