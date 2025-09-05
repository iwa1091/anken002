<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; // Requestは引き続き使用しますが、updateメソッドのバリデーションはFormRequestに委譲
use Carbon\Carbon; // Carbonライブラリをインポート
use App\Models\Attendance; // Attendanceモデルをインポート
use App\Models\User; // Userモデルをインポート（Attendanceとのリレーションシップのため）
use App\Models\BreakTime; // BreakTimeモデルをインポート
use App\Http\Requests\Admin\AdminAttendanceUpdateRequest; // 新しいForm Requestをインポート
use Illuminate\Support\Facades\DB; // トランザクションのためにDBファサードをインポート

class AttendanceController extends Controller
{
    /**
     * 管理者向けの勤怠一覧を表示します。
     * 指定された日付の勤怠データを取得し、ビューに渡します。
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $date  表示する日付 (YYYY-MM-DD形式)。指定がない場合は今日の日付。
     * @return \Illuminate\View\View
     */
    public function list(Request $request, $date = null)
    {
        // URLパラメータから日付を取得し、Carbonインスタンスに変換。
        // 指定がない場合は今日の日付を使用。
        $currentDate = $date ? Carbon::parse($date) : Carbon::today();

        // ビューに渡すための日付関連の変数
        // Bladeファイルが $currentMonth, $prevDay, $nextDay を期待しているため、これらを準備
        $currentMonth = $currentDate->copy(); // 現在の日付（タイトル表示にも使用）
        $prevDay = $currentDate->copy()->subDay(); // 前日
        $nextDay = $currentDate->copy()->addDay(); // 翌日

        // 指定された日付の勤怠データを取得
        // 'user' と 'breakTimes' リレーションシップをEager Loadして、N+1問題を回避
        $attendances = Attendance::with(['user', 'breakTimes'])
            ->whereDate('check_in_time', $currentDate->toDateString()) // check_in_time の日付部分でフィルタ
            ->orderBy('check_in_time', 'asc') // 出勤時刻で昇順ソート
            ->paginate(10); // 1ページあたり10件でページネーション

        // 各勤怠レコードの合計休憩時間を計算してセット
        $attendances->transform(function ($attendance) {
            $attendance->calculateAndSaveTotalBreakTime(); // Attendanceモデルのヘルパーメソッドを呼び出し
            return $attendance;
        });

        // 取得した勤怠データと日付関連の変数をビューに渡して表示
        // $prevMonth, $nextMonth ではなく $prevDay, $nextDay を渡す
        return view('admin.attendance.list', compact('attendances', 'currentMonth', 'prevDay', 'nextDay'));
    }

    /**
     * 管理者向けの勤怠詳細を表示します。
     *
     * @param  int  $id 勤怠ID
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        // 勤怠データをIDで検索し、ユーザー情報と休憩時間をEager Load
        $attendance = Attendance::with(['user', 'breakTimes'])->findOrFail($id);

        // 詳細表示時にも合計休憩時間を計算してセット
        $attendance->calculateAndSaveTotalBreakTime();

        return view('admin.attendance.show', compact('attendance'));
    }

    /**
     * 勤怠編集フォームを表示します。
     *
     * @param  int  $id 勤怠ID
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $attendance = Attendance::with(['user', 'breakTimes'])->findOrFail($id);
        return view('admin.attendance.edit', compact('attendance'));
    }

    /**
     * 勤怠データを更新します。
     *
     * @param  \App\Http\Requests\Admin\AdminAttendanceUpdateRequest  $request
     * @param  int  $id 勤怠ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(AdminAttendanceUpdateRequest $request, $id) // ★修正: Request $request を AdminAttendanceUpdateRequest $request に変更 ★
    {
       
        $attendance = Attendance::with('breakTimes')->findOrFail($id); // breakTimesもロードしておく

        // バリデーションはAdminAttendanceUpdateRequestによって自動的に行われるため、ここでは不要
        // $request->validate([...]); // ★削除★

        DB::beginTransaction(); // トランザクション開始
        try {


            // 勤怠の日付を取得（check_in_timeから日付部分を取得するのが安全）
            $attendanceDate = $attendance->date->toDateString();

            // 出勤・退勤時刻の更新
            $checkIn = $request->input('check_in_time') ? Carbon::parse($attendanceDate . ' ' . $request->input('check_in_time')) : null;
            $checkOut = $request->input('check_out_time') ? Carbon::parse($attendanceDate . ' ' . $request->input('check_out_time')) : null;

            // 備考の更新
            $remarks = $request->input('remarks');

            // Attendanceモデルの更新（break_timeとworking_timeは後で再計算）
            $attendance->check_in_time = $checkIn;
            $attendance->check_out_time = $checkOut;
            $attendance->remarks = $remarks;
            $attendance->save(); // いったん保存してIDを確定（もし新規作成の場合）

            // 休憩時間の更新
            $requestedBreaks = $request->input('breaks');

            // 既存の休憩時間を全て削除
            $attendance->breakTimes()->delete();

            // 新しい休憩時間を挿入
            if (!empty($requestedBreaks) && is_array($requestedBreaks)) {
                foreach ($requestedBreaks as $break) {
                    $breakStartTime = $break['start'] ?? null;
                    $breakEndTime = $break['end'] ?? null;

                    // 両方の時刻が入力されている場合のみBreakTimeレコードを作成
                    if (!empty($breakStartTime) && !empty($breakEndTime)) {
                        BreakTime::create([
                            'attendance_id' => $attendance->id,
                            'break_start_time' => Carbon::parse($attendanceDate . ' ' . $breakStartTime),
                            'break_end_time' => Carbon::parse($attendanceDate . ' ' . $breakEndTime),
                        ]);
                    }
                }
            }

            // 勤怠データ更新後、合計休憩時間と勤務時間を再計算して保存
            // calculateAndSaveTotalBreakTime()メソッド内でsave()が呼ばれるため、ここでは不要
            $attendance->calculateAndSaveTotalBreakTime();

            DB::commit(); // トランザクションコミット

            return redirect()->route('admin.attendance.show', $attendance->id)->with('success', '勤怠データが正常に更新されました。');
        } catch (\Exception $e) {
            DB::rollBack(); // エラーが発生した場合はロールバック
            return redirect()->back()->with('error', '勤怠データの更新中にエラーが発生しました: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * 勤怠データを削除します。
     *
     * @param  int  $id 勤怠ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete($id)
    {
        $attendance = Attendance::findOrFail($id);
        $attendance->delete();

        return redirect()->route('admin.attendance.list')->with('success', '勤怠データが正常に削除されました。');
    }
}
