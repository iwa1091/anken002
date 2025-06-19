<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User; // スタッフユーザーを操作するため
use App\Models\Attendance; // 勤怠データを操作するため
use App\Models\Role; // ロール情報を取得するため
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response; // CSV出力用
use Carbon\Carbon;

class AdminStaffController extends Controller
{
    /**
     * Display a listing of staff members for administrators.
     * 管理者向けのスタッフ一覧を表示します。
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // 'staff' ロールを持つユーザーを取得
        $staffRole = Role::where('name', 'staff')->first();

        // スタッフユーザーが存在する場合、そのユーザーリストをページネーションで取得
        $staffs = collect();
        if ($staffRole) {
            $staffs = User::where('role_id', $staffRole->id)
                ->orderBy('name')
                ->paginate(15); // 1ページあたり15件表示
        }

        return view('admin.staff.list', compact('staffs'));
    }

    /**
     * Display the monthly attendance list for a specific staff member.
     * 特定のスタッフの月次勤怠一覧を表示します。
     *
     * @param  int  $id  スタッフ（ユーザー）ID
     * @param  string|null  $month  (YYYY-MM形式、例: 2023-04)
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function attendanceList(int $id, ?string $month = null)
    {
        // 指定されたIDのスタッフ（ユーザー）を取得
        $staff = User::with('role')->findOrFail($id);

        // スタッフでなければアクセスを拒否
        if (!$staff->role || $staff->role->name !== 'staff') {
            return redirect()->route('admin.staff.list')->with('error', '指定されたユーザーはスタッフではありません。');
        }

        // 表示対象月を設定
        $targetMonth = $month ? Carbon::parse($month) : Carbon::today();
        $startDate = $targetMonth->copy()->startOfMonth();
        $endDate = $targetMonth->copy()->endOfMonth();

        // 選択された月の勤怠データを取得（休憩時間もEager Load）
        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $staff->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->get(); // 月全体のデータを取得するため paginate は使用しない

        // 各勤怠レコードの合計休憩時間と実働時間を計算
        $attendances->transform(function ($attendance) {
            $totalBreakMinutes = 0;
            foreach ($attendance->breakTimes as $breakTime) {
                if ($breakTime->break_start_time && $breakTime->break_end_time) {
                    $totalBreakMinutes += $breakTime->break_start_time->diffInMinutes($breakTime->break_end_time);
                }
            }
            $attendance->total_break_time_formatted = Carbon::parse('00:00:00')->addMinutes($totalBreakMinutes)->format('H:i');

            if ($attendance->check_in_time && $attendance->check_out_time) {
                $workMinutes = $attendance->check_in_time->diffInMinutes($attendance->check_out_time);
                $actualWorkMinutes = $workMinutes - $totalBreakMinutes;
                $attendance->actual_work_time_formatted = Carbon::parse('00:00:00')->addMinutes($actualWorkMinutes)->format('H:i');
            } else {
                $attendance->actual_work_time_formatted = null;
            }

            return $attendance;
        });

        // 月の前後ナビゲーション用データ
        $previousMonth = $targetMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $targetMonth->copy()->addMonth()->format('Y-m');

        return view('admin.staff.attendance', compact(
            'staff',
            'attendances',
            'targetMonth',
            'previousMonth',
            'nextMonth'
        ));
    }

    /**
     * Export the monthly attendance data for a specific staff member as a CSV file.
     * 特定のスタッフの月次勤怠データをCSVファイルとして出力します。
     *
     * @param  int  $id  スタッフ（ユーザー）ID
     * @param  string  $month  (YYYY-MM形式、例: 2023-04)
     * @return \Illuminate\Http\Response
     */
    public function exportCsv(int $id, string $month)
    {
        // 指定されたIDのスタッフ（ユーザー）を取得
        $staff = User::with('role')->findOrFail($id);

        // スタッフでなければエラー
        if (!$staff->role || $staff->role->name !== 'staff') {
            return redirect()->route('admin.staff.list')->with('error', '指定されたユーザーはスタッフではありません。');
        }

        // 表示対象月を設定
        $targetMonth = Carbon::parse($month);
        $startDate = $targetMonth->copy()->startOfMonth();
        $endDate = $targetMonth->copy()->endOfMonth();

        // 選択された月の勤怠データを取得（休憩時間もEager Load）
        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $staff->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->get();

        $filename = 'attendance_' . $staff->name . '_' . $targetMonth->format('Ym') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function() use ($attendances, $staff) {
            $file = fopen('php://output', 'w');
            // BOMを書き込むことでExcelでの文字化けを防ぐ
            fwrite($file, "\xEF\xBB\xBF");

            // CSVヘッダー
            fputcsv($file, ['日付', '出勤時刻', '退勤時刻', '休憩時間', '実労働時間', '備考']);

            foreach ($attendances as $attendance) {
                $totalBreakMinutes = 0;
                foreach ($attendance->breakTimes as $breakTime) {
                    if ($breakTime->break_start_time && $breakTime->break_end_time) {
                        $totalBreakMinutes += $breakTime->break_start_time->diffInMinutes($breakTime->break_end_time);
                    }
                }
                $totalBreakTimeFormatted = Carbon::parse('00:00:00')->addMinutes($totalBreakMinutes)->format('H:i');

                $actualWorkTimeFormatted = null;
                if ($attendance->check_in_time && $attendance->check_out_time) {
                    $workMinutes = $attendance->check_in_time->diffInMinutes($attendance->check_out_time);
                    $actualWorkMinutes = $workMinutes - $totalBreakMinutes;
                    $actualWorkTimeFormatted = Carbon::parse('00:00:00')->addMinutes($actualWorkMinutes)->format('H:i');
                }

                fputcsv($file, [
                    $attendance->date->format('Y-m-d'),
                    $attendance->check_in_time ? $attendance->check_in_time->format('H:i') : '',
                    $attendance->check_out_time ? $attendance->check_out_time->format('H:i') : '',
                    $totalBreakTimeFormatted,
                    $actualWorkTimeFormatted,
                    $attendance->remarks,
                ]);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
