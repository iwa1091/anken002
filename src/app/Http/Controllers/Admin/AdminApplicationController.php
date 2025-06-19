<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance; // 勤怠レコードを更新するために必要
use App\Models\BreakTime;  // 休憩レコードを更新するために必要
use Carbon\Carbon;         // 日付操作のために必要

class AdminApplicationController extends Controller
{
    /**
     * Display a listing of correction requests for administrators.
     * 管理者向けの勤怠修正申請一覧を表示します。
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // 全ての修正申請を取得し、最新のものが上に来るようにソート
        // userとattendanceリレーションをEager Load
        $correctionRequests = CorrectionRequest::with(['user', 'attendance'])
            ->orderBy('status', 'asc') // 承認待ちを優先的に表示
            ->orderBy('created_at', 'desc')
            ->paginate(15); // 1ページあたり15件表示

        return view('admin.application.list', compact('correctionRequests'));
    }

    /**
     * Display the approval form for a specific correction request.
     * 指定された勤怠修正申請の承認フォームを表示します。
     *
     * @param  int  $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showApproveForm(int $id)
    {
        // 修正申請を取得 (ユーザーと勤怠データをEager Load)
        $correctionRequest = CorrectionRequest::with(['user', 'attendance'])
            ->findOrFail($id);

        return view('admin.application.approve', compact('correctionRequest'));
    }

    /**
     * Process the approval or rejection of a correction request.
     * 勤怠修正申請の承認または却下を処理します。
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve(Request $request, int $id)
    {
        $request->validate([
            'status' => ['required', 'string', 'in:approved,rejected'], // 'approved' または 'rejected'
        ]);

        $correctionRequest = CorrectionRequest::with('attendance')->findOrFail($id);

        // すでに処理済みの場合は何もしない
        if ($correctionRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'この申請はすでに処理済みです。');
        }

        DB::beginTransaction();
        try {
            // 申請ステータスを更新
            $correctionRequest->status = $request->status;
            $correctionRequest->save();

            // 申請が承認された場合、勤怠データを更新
            if ($request->status === 'approved') {
                $attendance = $correctionRequest->attendance;

                // 出勤時刻の修正
                if ($correctionRequest->requested_check_in_time) {
                    $attendance->check_in_time = $correctionRequest->requested_check_in_time;
                }
                // 退勤時刻の修正
                if ($correctionRequest->requested_check_out_time) {
                    $attendance->check_out_time = $correctionRequest->requested_check_out_time;
                }

                // 休憩時間の修正（既存を削除し、新しいものを挿入）
                if (!empty($correctionRequest->requested_breaks)) {
                    // 既存の休憩時間を削除
                    $attendance->breakTimes()->delete();

                    // 新しい休憩時間を挿入
                    foreach ($correctionRequest->requested_breaks as $break) {
                        BreakTime::create([
                            'attendance_id' => $attendance->id,
                            'break_start_time' => Carbon::parse($attendance->date->toDateString() . ' ' . $break['start']),
                            'break_end_time' => Carbon::parse($attendance->date->toDateString() . ' ' . $break['end']),
                        ]);
                    }
                } else {
                    // 休憩時間の申請がない、または空の場合は既存の休憩時間を全て削除
                    $attendance->breakTimes()->delete();
                }

                $attendance->save(); // 勤怠データを保存
            }

            DB::commit();
            return redirect()->route('admin.application.list')->with('success', '申請が正常に処理されました。');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', '申請処理中にエラーが発生しました: ' . $e->getMessage());
        }
    }
}
