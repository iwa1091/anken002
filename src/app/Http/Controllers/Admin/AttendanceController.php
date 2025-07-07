<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User; // スタッフの勤怠を検索するためにUserモデルが必要になる場合がある
use App\Models\BreakTime; // 休憩時間の計算や更新のために必要
use App\Http\Requests\Admin\AdminAttendanceUpdateRequest; // 管理者による勤怠更新リクエスト
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Display a listing of daily attendance records for administrators.
     * 管理者向けの日次勤怠一覧を表示します。
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $date (YYYY-MM-DD形式、例: 2023-04-15)
     * @return \Illuminate\View\View
     */
    public function listDaily(Request $request, ?string $date = null)
    {
        // 表示対象日を設定
        $targetDate = $date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString();

        // 選択された日の勤怠データを取得（休憩時間とユーザーもEager Load）
        $attendances = Attendance::with(['user.role', 'breakTimes'])
            ->where('date', $targetDate)
            ->orderBy('check_in_time', 'asc') // 出勤時刻順にソート
            ->paginate(15); // 1ページあたり15件表示

        // 各勤怠レコードの合計休憩時間を計算
        $attendances->getCollection()->transform(function ($attendance) {
            $totalBreakMinutes = 0;
            foreach ($attendance->breakTimes as $breakTime) {
                if ($breakTime->break_start_time && $breakTime->break_end_time) {
                    $totalBreakMinutes += $breakTime->break_start_time->diffInMinutes($breakTime->break_end_time);
                }
            }
            // Carbon期間オブジェクトを作成し、H:i形式にフォーマット
            $attendance->total_break_time_formatted = Carbon::parse('00:00:00')->addMinutes($totalBreakMinutes)->format('H:i');

            // 勤務時間の計算 (出勤〜退勤) - 休憩時間
            if ($attendance->check_in_time && $attendance->check_out_time) {
                $workMinutes = $attendance->check_in_time->diffInMinutes($attendance->check_out_time);
                $actualWorkMinutes = $workMinutes - $totalBreakMinutes;
                $attendance->actual_work_time_formatted = Carbon::parse('00:00:00')->addMinutes($actualWorkMinutes)->format('H:i');
            } else {
                $attendance->actual_work_time_formatted = null;
            }

            return $attendance;
        });

        // 日付の前後ナビゲーション用データ
        $previousDay = Carbon::parse($targetDate)->subDay()->format('Y-m-d');
        $nextDay = Carbon::parse($targetDate)->addDay()->format('Y-m-d');

        return view('admin.attendance.list', compact(
            'attendances',
            'targetDate',
            'previousDay',
            'nextDay'
        ));
    }

    /**
     * Display the specified attendance record details for administrators.
     * 指定された勤怠データ（管理者向け）の詳細を表示します。
     *
     * @param  int  $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(int $id)
    {
        // 指定されたIDの勤怠レコードを、ユーザーと休憩時間もEager Loadして取得
        $attendance = Attendance::with(['user.role', 'breakTimes'])
            ->findOrFail($id);

        // 合計休憩時間を計算
        $totalBreakMinutes = 0;
        foreach ($attendance->breakTimes as $breakTime) {
            if ($breakTime->break_start_time && $breakTime->break_end_time) {
                $totalBreakMinutes += $breakTime->break_start_time->diffInMinutes($breakTime->break_end_time);
            }
        }
        $attendance->total_break_time_formatted = Carbon::parse('00:00:00')->addMinutes($totalBreakMinutes)->format('H:i');

        // 勤務時間の計算
        if ($attendance->check_in_time && $attendance->check_out_time) {
            $workMinutes = $attendance->check_in_time->diffInMinutes($attendance->check_out_time);
            $actualWorkMinutes = $workMinutes - $totalBreakMinutes;
            $attendance->actual_work_time_formatted = Carbon::parse('00:00:00')->addMinutes($actualWorkMinutes)->format('H:i');
        } else {
            $attendance->actual_work_time_formatted = null;
        }

        return view('admin.attendance.show', compact('attendance'));
    }

    /**
     * Update the specified attendance record.
     * 指定された勤怠データを更新します。
     *
     * @param  \App\Http\Requests\Admin\AdminAttendanceUpdateRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(AdminAttendanceUpdateRequest $request, int $id)
    {
        $attendance = Attendance::findOrFail($id);

        DB::beginTransaction();
        try {
            // 勤怠レコードを更新
            $attendance->check_in_time = $request->check_in_time ?
                Carbon::parse($attendance->date->toDateString() . ' ' . $request->check_in_time) : null;
            $attendance->check_out_time = $request->check_out_time ?
                Carbon::parse($attendance->date->toDateString() . ' ' . $request->check_out_time) : null;
            $attendance->remarks = $request->remarks;
            // status の更新は、手動更新時にも自動調整ロジックを検討するか、別途ルールを設ける
            // 例: check_out_time があれば '退勤済'、check_in_time があれば '出勤中'
            if ($attendance->check_out_time) {
                $attendance->status = '退勤済';
            } elseif ($attendance->check_in_time) {
                $attendance->status = '出勤中';
            } else {
                $attendance->status = '勤務外';
            }

            $attendance->save();

            // 休憩時間の更新（既存を削除し、新しいものを挿入）
            $attendance->breakTimes()->delete(); // 既存の休憩時間を全て削除

            if (!empty($request->breaks)) {
                foreach ($request->breaks as $break) {
                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start_time' => Carbon::parse($attendance->date->toDateString() . ' ' . $break['start']),
                        'break_end_time' => Carbon::parse($attendance->date->toDateString() . ' ' . $break['end']),
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('admin.attendance.show', $attendance->id)->with('success', '勤怠データが更新されました。');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', '勤怠データの更新に失敗しました: ' . $e->getMessage());
        }
    }
}
