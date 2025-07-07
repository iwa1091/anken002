<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BreakTime;
// use App\Models\CorrectionRequest; // 勤怠修正申請モデル - ApplicationControllerに移動するため削除
use App\Http\Requests\ClockInRequest; // 出勤打刻のリクエスト
use App\Http\Requests\ClockOutRequest; // 退勤打刻のリクエスト
// use App\Http\Requests\CorrectionRequestStoreRequest; // 修正申請のリクエストバリデーション - ApplicationControllerに移動するため削除
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // 認証ユーザーのIDを取得
use Illuminate\Support\Facades\DB; // トランザクション処理のためにDBファサードを使用
use Carbon\Carbon; // 日付・時刻操作のためにCarbonを使用

class AttendanceController extends Controller
{
    /**
     * Display the main attendance page (Clock-in/Clock-out view).
     * 勤怠メインページ（打刻画面）を表示します。
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();
        $currentDate = Carbon::today(); // FN018: 現在の日時情報取得機能のためにビューに渡す
        $currentTime = Carbon::now(); // 追加: 現在時刻を取得しビューに渡す
        $today = $currentDate->toDateString();
        // 今日の勤怠レコードを取得（存在しない場合はnull）
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        // 今日の最新の休憩レコードを取得
        $latestBreak = null;
        if ($attendance) {
            $latestBreak = BreakTime::where('attendance_id', $attendance->id)
                ->whereNull('break_end_time') // 終了していない休憩
                ->orderBy('break_start_time', 'desc') // 開始時刻が最新の休憩
                ->first();
        }

        // FN019: ステータス確認機能 - 現在の勤怠ステータスを判定
        $attendanceStatus = '勤務外'; // デフォルトステータス
        if ($attendance) {
            if ($attendance->check_out_time) {
                $attendanceStatus = '退勤済';
            } elseif ($latestBreak) {
                $attendanceStatus = '休憩中';
            } elseif ($attendance->check_in_time) {
                $attendanceStatus = '出勤中';
            }
        }

        // ビューに渡すデータ
        return view('attendance.index', compact('currentDate', 'currentTime', 'attendanceStatus'));
    }

    /**
     * Handle the clock-in (punch-in) request.
     * 出勤打刻を処理します。
     *
     * @param  \App\Http\Requests\ClockInRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function checkIn(ClockInRequest $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        // 既に今日の勤怠レコードがあるか確認 (FN020-2)
        $existingAttendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($existingAttendance && $existingAttendance->check_in_time) {
            return redirect()->back()->with('error', 'すでに出勤打刻がされています。');
        }

        DB::beginTransaction(); // トランザクション開始
        try {
            // 勤怠レコードを作成または更新 (FN020-3, FN020-4)
            $attendance = Attendance::updateOrCreate(
                ['user_id' => $user->id, 'date' => $today],
                [
                    'check_in_time' => Carbon::now(),
                    'status' => '出勤中',
                    // check_out_time はnullのまま
                    // remarks もnullのまま
                ]
            );

            DB::commit(); // コミット
            return redirect()->back()->with('success', '出勤打刻が完了しました。');
        } catch (\Exception $e) {
            DB::rollBack(); // ロールバック
            return redirect()->back()->with('error', '出勤打刻に失敗しました。' . $e->getMessage());
        }
    }

    /**
     * Handle the break-in request.
     * 休憩開始を処理します。
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function breakIn(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        // 今日の勤怠レコードを取得
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        // 勤怠レコードがない、または出勤打刻がされていない場合
        if (!$attendance || !$attendance->check_in_time) {
            return redirect()->back()->with('error', '出勤打刻がされていません。');
        }

        // すでに休憩中の休憩レコードがないか確認 (FN021-2のビジネスロジック)
        $latestBreak = BreakTime::where('attendance_id', $attendance->id)
            ->whereNull('break_end_time')
            ->first();

        if ($latestBreak) {
            return redirect()->back()->with('error', 'すでに休憩中です。');
        }

        DB::beginTransaction(); // トランザクション開始
        try {
            // 新しい休憩レコードを作成 (FN021-5a)
            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_start_time' => Carbon::now(),
                // break_end_time はnullのまま
            ]);

            // 勤怠ステータスを休憩中に更新 (FN021-3)
            $attendance->status = '休憩中';
            $attendance->save();

            DB::commit(); // コミット
            return redirect()->back()->with('success', '休憩を開始しました。');
        } catch (\Exception $e) {
            DB::rollBack(); // ロールバック
            return redirect()->back()->with('error', '休憩開始に失敗しました。' . $e->getMessage());
        }
    }

    /**
     * Handle the break-out request.
     * 休憩終了を処理します。
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function breakOut(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        // 今日の勤怠レコードを取得
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        // 勤怠レコードがない、または出勤打刻がされていない場合
        if (!$attendance || !$attendance->check_in_time) {
            return redirect()->back()->with('error', '出勤打刻がされていません。');
        }

        // 未終了の休憩レコードがないか確認 (FN021-5のビジネスロジック)
        $latestBreak = BreakTime::where('attendance_id', $attendance->id)
            ->whereNull('break_end_time')
            ->first();

        if (!$latestBreak) {
            return redirect()->back()->with('error', '休憩が開始されていません。');
        }

        DB::beginTransaction(); // トランザクション開始
        try {
            // 休憩終了時刻を更新 (FN021-7)
            $latestBreak->break_end_time = Carbon::now();
            $latestBreak->save();

            // 勤怠ステータスを出勤中に更新 (FN021-6)
            $attendance->status = '出勤中';
            $attendance->save();

            DB::commit(); // コミット
            return redirect()->back()->with('success', '休憩を終了しました。');
        } catch (\Exception $e) {
            DB::rollBack(); // ロールバック
            return redirect()->back()->with('error', '休憩終了に失敗しました。' . $e->getMessage());
        }
    }

    /**
     * Handle the clock-out (punch-out) request.
     * 退勤打刻を処理します。
     *
     * @param  \App\Http\Requests\ClockOutRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function checkOut(ClockOutRequest $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        // 今日の勤怠レコードを取得
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        // 勤怠レコードがない、または出勤打刻がされていない場合
        if (!$attendance || !$attendance->check_in_time) {
            return redirect()->back()->with('error', '出勤打刻がされていません。');
        }

        // すでに退勤打刻がされている場合 (FN022-2)
        if ($attendance->check_out_time) {
            return redirect()->back()->with('error', 'すでに退勤打刻がされています。');
        }

        // 未終了の休憩レコードがないか確認 (退勤前の必須チェック)
        $latestBreak = BreakTime::where('attendance_id', $attendance->id)
            ->whereNull('break_end_time')
            ->first();

        if ($latestBreak) {
            return redirect()->back()->with('error', '休憩が終了されていません。休憩を終了してから退勤してください。');
        }

        DB::beginTransaction(); // トランザクション開始
        try {
            // 退勤時刻を更新 (FN022-5)
            $attendance->check_out_time = Carbon::now();
            $attendance->status = '退勤済'; // FN022-4
            $attendance->save();

            DB::commit(); // コミット
            // FN022-3: 「お疲れ様でした。」とメッセージが表示される
            return redirect()->back()->with('success', 'お疲れ様でした。退勤打刻が完了しました。');
        } catch (\Exception $e) {
            DB::rollBack(); // ロールバック
            return redirect()->back()->with('error', '退勤打刻に失敗しました。' . $e->getMessage());
        }
    }

    /**
     * Display a listing of the user's attendance records.
     * ユーザーの勤怠一覧を表示します。
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $month  (YYYY-MM形式、例: 2023-04)
     * @return \Illuminate\View\View
     */
    public function list(Request $request, ?string $month = null)
    {
        $user = Auth::user();

        // 表示対象月を設定 (FN024-1)
        // Blade側で $currentMonth を期待しているので、変数名を合わせる
        $currentMonth = $month ? Carbon::parse($month) : Carbon::today();
        $startDate = $currentMonth->copy()->startOfMonth();
        $endDate = $currentMonth->copy()->endOfMonth();

        // 選択された月の勤怠データを取得 (FN023-1)
        $attendances = Attendance::with('breakTimes') // 休憩時間をEager Load
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get(); // すべての内容を表示

        // 各勤怠レコードの合計休憩時間と合計勤務時間を計算
        // Collectionに対して直接transformを呼び出す
        $attendances->transform(function ($attendance) {
            $totalBreakSeconds = 0;
            foreach ($attendance->breakTimes as $breakTime) {
                if ($breakTime->break_start_time && $breakTime->break_end_time) {
                    $totalBreakSeconds += $breakTime->break_start_time->diffInSeconds($breakTime->break_end_time);
                }
            }
            $attendance->total_break_time = $totalBreakSeconds; // 秒数で保存

            $totalWorkingSeconds = 0;
            if ($attendance->check_in_time && $attendance->check_out_time) {
                // 勤務時間から休憩時間を引く
                $totalWorkingSeconds = $attendance->check_in_time->diffInSeconds($attendance->check_out_time) - $totalBreakSeconds;
            }
            $attendance->total_working_time = $totalWorkingSeconds; // 秒数で保存

            return $attendance;
        });

        // 月の前後ナビゲーション用データ (FN024-2, FN024-3)
        // Carbonインスタンスのまま渡すように修正
        $prevMonth = $currentMonth->copy()->subMonth();
        $nextMonth = $currentMonth->copy()->addMonth();

        return view('attendance.list', compact(
            'attendances',
            'currentMonth', // 変数名をBladeに合わせて修正
            'prevMonth',     // Carbonインスタンスのまま渡す
            'nextMonth'      // Carbonインスタンスのまま渡す
        ));
    }

    /**
     * Display the specified attendance record details for the user.
     * 指定された勤怠データ（一般ユーザー）の詳細を表示します。
     *
     * @param  int  $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(int $id)
    {
        $user = Auth::user();

        // 指定されたIDの勤怠レコードを、ログイン中のユーザーのものであることを確認して取得 (FN026-1, FN026-2, FN026-3)
        // 関連する休憩時間と修正申請もEager Load
        $attendance = Attendance::with(['breakTimes', 'correctionRequests'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // 勤怠詳細ビューを返す
        return view('attendance.detail', compact('attendance'));
    }

    // /**
    //  * Handle the correction request for a specific attendance record.
    //  * 特定の勤怠レコードに対する修正申請を処理します。
    //  *
    //  * @param  \App\Http\Requests\CorrectionRequestStoreRequest  $request
    //  * @param  int  $id  修正対象の勤怠ID
    //  * @return \Illuminate\Http\RedirectResponse
    //  */
    // public function requestCorrection(CorrectionRequestStoreRequest $request, int $id)
    // {
    //     $user = Auth::user();

    //     // 修正対象の勤怠レコードをユーザーIDで確認して取得
    //     $attendance = Attendance::where('id', $id)
    //         ->where('user_id', $user->id)
    //         ->firstOrFail();

    //     DB::beginTransaction();
    //     try {
    //         // 修正申請レコードを作成 (FN030-1, FN030-2)
    //         CorrectionRequest::create([
    //             'attendance_id' => $attendance->id,
    //             'user_id' => $user->id,
    //             'type' => $request->type,
    //             'requested_check_in_time' => $request->requested_check_in_time ? Carbon::parse($attendance->date->toDateString() . ' ' . $request->requested_check_in_time) : null,
    //             'requested_check_out_time' => $request->requested_check_out_time ? Carbon::parse($attendance->date->toDateString() . ' ' . $request->requested_check_out_time) : null,
    //             'requested_breaks' => $request->requested_breaks ?? [], // 配列で受け取り、モデルのキャストでJSON保存
    //             'reason' => $request->reason,
    //             'status' => 'pending', // デフォルトは承認待ち
    //         ]);

    //         DB::commit();
    //         return redirect()->back()->with('success', '修正申請が送信されました。');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return redirect()->back()->with('error', '修正申請の送信に失敗しました。' . $e->getMessage());
    //     }
    // }
}
