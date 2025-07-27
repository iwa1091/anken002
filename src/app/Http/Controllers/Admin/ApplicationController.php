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

class ApplicationController extends Controller
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
        // URLから 'tab' クエリパラメータを取得し、デフォルトは 'pending' とする
        $activeTab = $request->query('tab', 'pending');

        // 全ての修正申請を取得し、最新のものが上に来るようにソート
        // userとattendanceリレーションをEager Load
        $correctionRequests = CorrectionRequest::with(['user', 'attendance'])
            ->orderBy('status', 'asc') // 承認待ちを優先的に表示
            ->orderBy('created_at', 'desc')
            ->paginate(15); // 1ページあたり15件表示

        // 取得した修正申請データとアクティブなタブ情報をビューに渡して表示
        return view('admin.application.list', compact('correctionRequests', 'activeTab'));
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
            // 承認された場合、承認日時と承認者IDを記録
            if ($request->status === 'approved') {
                $correctionRequest->approved_at = Carbon::now();
                $correctionRequest->approved_by = Auth::id(); // ログイン中の管理者のIDを記録
            }
            $correctionRequest->save();

            // 申請が承認された場合、勤怠データを更新
            if ($request->status === 'approved') {
                $attendance = $correctionRequest->attendance;

                // 出勤時刻の修正
                if ($correctionRequest->requested_check_in_time) {
                    // requested_check_in_timeが文字列として保存されている場合、Carbon::parseで変換
                    $attendance->check_in_time = Carbon::parse($correctionRequest->requested_check_in_time);
                }
                // 退勤時刻の修正
                if ($correctionRequest->requested_check_out_time) {
                    // requested_check_out_timeが文字列として保存されている場合、Carbon::parseで変換
                    $attendance->check_out_time = Carbon::parse($correctionRequest->requested_check_out_time);
                }

                // 休憩時間の修正（既存を削除し、新しいものを挿入）
                // requested_breaksがJSON文字列の場合、デコードして使用
                $requestedBreaks = is_string($correctionRequest->requested_breaks)
                                   ? json_decode($correctionRequest->requested_breaks, true)
                                   : $correctionRequest->requested_breaks;

                if (!empty($requestedBreaks) && is_array($requestedBreaks)) {
                    // 既存の休憩時間を削除
                    $attendance->breakTimes()->delete();

                    // 新しい休憩時間を挿入
                    foreach ($requestedBreaks as $break) {
                        // 勤怠の日付と休憩時刻を組み合わせてCarbonインスタンスを作成
                        $breakStartTime = Carbon::parse($attendance->date->toDateString() . ' ' . ($break['start'] ?? '00:00'));
                        $breakEndTime = Carbon::parse($attendance->date->toDateString() . ' ' . ($break['end'] ?? '00:00'));

                        BreakTime::create([
                            'attendance_id' => $attendance->id,
                            'break_start_time' => $breakStartTime,
                            'break_end_time' => $breakEndTime,
                        ]);
                    }
                } else {
                    // 休憩時間の申請がない、または空の場合は既存の休憩時間を全て削除
                    $attendance->breakTimes()->delete();
                }

                $attendance->save(); // 勤怠データを保存
                // 勤怠データ更新後、合計休憩時間と勤務時間を再計算して保存
                $attendance->calculateAndSaveTotalBreakTime();
            }

            DB::commit();
            // ★修正: 申請一覧画面へのリダイレクトから、現在の詳細画面へのリダイレクトに変更 ★
            return redirect()->back()->with('success', '申請が正常に処理されました。');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', '申請処理中にエラーが発生しました: ' . $e->getMessage());
        }
    }
}
