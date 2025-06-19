<?php

namespace App\Http\Controllers;

use App\Models\CorrectionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // 認証ユーザーのIDを取得するために使用

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
        $correctionRequests = CorrectionRequest::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(10); // 1ページあたり10件表示

        // 申請一覧ビューを返す
        return view('application.list', compact('correctionRequests'));
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
}
