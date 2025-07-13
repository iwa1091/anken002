<?php

namespace App\Http\Controllers;

use App\Models\CorrectionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // 認証ユーザーのIDを取得するために使用
use Illuminate\Support\Facades\DB; // トランザクション用
use App\Http\Requests\CorrectionRequestStoreRequest; // 新しく作成するForm Request

class ApplicationController extends Controller
{
    /**
     * Display a listing of the user's correction requests.
     * ユーザーの勤怠修正申請の一覧を表示します。
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // ログイン中のユーザーIDを取得
        $userId = Auth::id();

        // ログイン中のユーザーが提出した修正申請のみを取得し、ページネーション
        // 最新の申請が上に来るように降順でソート
        // 承認待ちの申請を取得
        $pendingApplications = CorrectionRequest::where('user_id', $userId)
            ->where('status', 'pending') // ステータスが 'pending' のものを取得
            ->orderBy('created_at', 'desc')
            ->get(); // ページネーションではなく、全て取得するように変更

        // 承認済みの申請を取得
        $approvedApplications = CorrectionRequest::where('user_id', $userId)
            ->where('status', 'approved') // ステータスが 'approved' のものを取得
            ->orderBy('created_at', 'desc')
            ->get(); // ページネーションではなく、全て取得するように変更

        // 申請一覧ビューを返す
        return view('application.list', compact('pendingApplications', 'approvedApplications'));
    }

    /**
     * Display the specified correction request.
     * 指定された勤怠修正申請の詳細を表示します。
     *
     * @param  int  $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(int $id)
    {
        // ログイン中のユーザーIDを取得
        $userId = Auth::id();

        // 指定されたIDの修正申請を、ログイン中のユーザーのものであることを確認して取得
        // 存在しない場合や、他のユーザーの申請であれば404エラー（NotFoundHttpException）を発生させる
        $correctionRequest = CorrectionRequest::with(['user', 'attendance']) // 関連するユーザーと勤怠データをEager Load
            ->where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        // 申請詳細ビューを返す
        return view('application.detail', compact('correctionRequest'));
    }

    /**
     * Store a newly created correction request in storage.
     * 新しい勤怠修正申請を保存します。（FN030 修正申請機能）
     *
     * @param  \App\Http\Requests\CorrectionRequestStoreRequest  $request  バリデーション済みのリクエスト
     * @param  int  $attendance_id  対象の勤怠ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeCorrectionRequest(CorrectionRequestStoreRequest $request, int $attendance_id)
    {
        // ログイン中のユーザーIDを取得
        $userId = Auth::id();

        // 既に同じ勤怠IDで承認待ちの申請が存在するかチェック
        $existingPendingRequest = CorrectionRequest::where('attendance_id', $attendance_id)
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->first();

        if ($existingPendingRequest) {
            // 既に承認待ちの申請がある場合、エラーメッセージと共にリダイレクト
            return back()->withErrors(['application_error' => 'この勤怠には既に承認待ちの修正申請があります。'])
                         ->withInput();
        }

        try {
            DB::transaction(function () use ($request, $attendance_id, $userId) {
                // モデルの$castsで自動的に処理されるため、json_encodeは不要
                // また、カラム名を'requested_breaks'、タイムカラム、理由カラムをモデルに合わせて修正
                CorrectionRequest::create([
                    'user_id' => $userId,
                    'attendance_id' => $attendance_id,
                    'requested_check_in_time' => $request->input('requested_check_in_time'),
                    'requested_check_out_time' => $request->input('requested_check_out_time'),
                    'requested_breaks' => $request->input('requested_breaks'), // カラム名を修正し、json_encodeを削除
                    'reason' => $request->input('reason'), // カラム名を修正
                    'status' => 'pending', // 承認待ち
                    'type' => 'attendance_correction', // ★★★ この行を追加しました ★★★
                ]);
            });
            // 修正申請が成功した場合
            return redirect()->route('stamp_correction_request.list')->with('success', '修正申請を提出しました。');

        } catch (\Exception $e) {
            // dd('Caught exception: ' . $e->getMessage()); // ★★★ 修正が完了したらこの行はコメントアウトまたは削除してください ★★★
            // エラーハンドリング
            // ログにエラーを記録することも推奨されます
            // \Log::error('Failed to store correction request: ' . $e->getMessage(), ['attendance_id' => $attendance_id, 'user_id' => $userId]);
            return back()->withErrors(['application_error' => '修正申請の提出に失敗しました。時間をおいて再度お試しください。'])
                         ->withInput();
        }
    }
}
