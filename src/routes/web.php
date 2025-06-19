<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\Auth\RegisteredUserController; // Auth\RegisterController の代わりに RegisteredUserController を使用
use App\Http\Controllers\Auth\AuthenticatedSessionController; // Auth\LoginController の代わりに AuthenticatedSessionController を使用
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController; // 管理者ログイン用
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController; // 管理者勤怠コントローラ
use App\Http\Controllers\Admin\StaffController as AdminStaffController; // 管理者スタッフコントローラ
use App\Http\Controllers\Admin\ApplicationController as AdminApplicationController; // 管理者申請コントローラ
use Laravel\Fortify\Http\Controllers\EmailVerificationPromptController; // Fortifyのメール認証ルート用
use Laravel\Fortify\Http\Controllers\VerifyEmailController; // Fortifyのメール認証ルート用
use Laravel\Fortify\Http\Controllers\ResendVerificationEmailController; // Fortifyのメール認証ルート用


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// --- 認証関連ルート (Fortifyが提供するルートに準拠) ---

// 一般ユーザー
// Fortifyが /register と /login のPOSTルートを提供するため、ここではGETルートのみ定義
Route::middleware('guest')->group(function () {
    // 会員登録画面（一般ユーザー）
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    // POST /register はFortifyが自動的に処理

    // ログイン画面（一般ユーザー）
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    // POST /login はFortifyが自動的に処理
});

// ログアウト (一般ユーザー)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

// Fortify のメール認証ルート
Route::get('/email/verify', [EmailVerificationPromptController::class, '__invoke'])
    ->middleware(['auth'])
    ->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
    ->middleware(['auth', 'signed', 'throttle:6,1'])
    ->name('verification.verify');

// メール認証再送ルート
// Note: 既存のRoute::post('/email/resend', ...) と重複するため、どちらか一方を有効にする
// Fortifyが提供するコントローラを使用することを推奨
Route::post('/email/verification-notification', [ResendVerificationEmailController::class, '__invoke'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.resend');


// --- 管理者認証関連ルート ---

Route::middleware('guest')->group(function () {
    // ログイン画面（管理者）
    // Fortifyのデフォルトとは異なるパスを使用
    Route::get('/admin/login', [AdminAuthenticatedSessionController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthenticatedSessionController::class, 'store']);
});

// ログアウト (管理者)
// admin middleware を使用して管理者のみがログアウトできるようにする
Route::middleware(['auth', 'can:admin-access'])->group(function () { // 'can:admin-access' はGateやPolicyで定義することを想定
    Route::post('/admin/logout', [AdminAuthenticatedSessionController::class, 'destroy'])->name('admin.logout');
});


// --- 一般ユーザー向け機能ルート ---
Route::middleware(['auth'])->group(function () {
    // 出勤登録画面（一般ユーザー）
    // 通常、打刻画面はGETで表示されるため、ルートを追加
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');
    // 出勤打刻処理
    Route::post('/attendance/checkin', [AttendanceController::class, 'checkIn'])->name('attendance.checkin');
    // 休憩開始処理
    Route::post('/attendance/breakin', [AttendanceController::class, 'breakIn'])->name('attendance.breakin');
    // 休憩終了処理
    Route::post('/attendance/breakout', [AttendanceController::class, 'breakOut'])->name('attendance.breakout');
    // 退勤打刻処理
    Route::post('/attendance/checkout', [AttendanceController::class, 'checkOut'])->name('attendance.checkout');


    // 勤怠一覧画面（一般ユーザー）
    Route::get('/attendance/list/{month?}', [AttendanceController::class, 'list'])->name('attendance.list'); // 月別表示対応

    // 勤怠詳細画面（一般ユーザー）
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show'])->name('attendance.detail');
    // 勤怠修正申請処理（一般ユーザー）
    Route::post('/attendance/detail/{id}/request-correction', [AttendanceController::class, 'requestCorrection'])->name('attendance.request-correction');


    // 申請一覧画面（一般ユーザー）
    Route::get('/application/list', [ApplicationController::class, 'index'])->name('application.list');
    // 申請詳細画面（一般ユーザー）- 修正不可
    Route::get('/application/detail/{id}', [ApplicationController::class, 'show'])->name('application.detail');

    // Fortifyメール認証後のリダイレクト先 (仮のルート)
    // 要件FN005「会員登録直後、打刻画面に遷移すること（メール認証を実装しなかった場合）」
    // と矛盾する可能性あり。メール認証後の遷移先を明確にする
    Route::get('/mypage', function() {
        return redirect()->route('attendance'); // メール認証後、打刻画面へリダイレクト
    })->middleware('verified')->name('mypage'); // 認証済みユーザーのみアクセス可能

    // 既存の '/email/resend' ルートはFortifyの verification.resend と重複するため、コメントアウトまたは削除を推奨
    // Route::post('/email/resend', function () {
    //     return back()->with('resent', true);
    // })->middleware(['auth'])->name('verification.resend');
});


// --- 管理者向け機能ルート ---
// 'can:admin-access' はGateまたはPolicyで管理者権限をチェックすることを想定
Route::middleware(['auth', 'can:admin-access'])->prefix('admin')->name('admin.')->group(function () {
    // 管理者ダッシュボード（デフォルト）
    Route::get('/dashboard', function () {
        return view('admin.dashboard'); // 管理者用のダッシュボードビューを返す
    })->name('dashboard');

    // 日次勤怠一覧画面（管理者）
    Route::get('/attendance/list/{date?}', [AdminAttendanceController::class, 'listDaily'])->name('attendance.list');
    // 勤怠詳細確認・修正画面（管理者）
    Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('attendance.show');
    Route::post('/attendance/{id}/update', [AdminAttendanceController::class, 'update'])->name('attendance.update');


    // スタッフ一覧画面（管理者）
    Route::get('/staff/list', [AdminStaffController::class, 'index'])->name('staff.list');
    // スタッフ別月次勤怠一覧画面（管理者）
    Route::get('/staff/{id}/attendance/{month?}', [AdminStaffController::class, 'attendanceList'])->name('staff.attendance');
    // CSV出力機能
    Route::get('/staff/{id}/attendance/{month}/csv', [AdminStaffController::class, 'exportCsv'])->name('staff.attendance.csv');


    // 修正申請一覧画面（管理者）
    Route::get('/application/list', [AdminApplicationController::class, 'index'])->name('application.list');
    // 修正申請承認画面（管理者）
    Route::get('/application/{id}/approve', [AdminApplicationController::class, 'showApproveForm'])->name('application.approve.show'); // 承認フォーム表示用
    Route::post('/application/{id}/approve', [AdminApplicationController::class, 'approve'])->name('application.approve');
});
