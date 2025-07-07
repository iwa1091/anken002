<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\Admin\ApplicationController as AdminApplicationController; // 管理者申請コントローラ
use Laravel\Fortify\Http\Controllers\EmailVerificationPromptController;
use Laravel\Fortify\Http\Controllers\VerifyEmailController;
use Laravel\Fortify\Http\Controllers\NewPasswordController; // パスワードリセットコントローラが必要に応じて
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController; // パスワードリセットリンクコントローラが必要に応じて
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController; // 2要素認証コントローラが必要に応じて
use Laravel\Fortify\Http\Controllers\ConfirmablePasswordController; // パスワード確認コントローラが必要に応じて
use Laravel\Fortify\Http\Controllers\PasswordController; // パスワード更新コントローラが必要に応じて
use Laravel\Fortify\Http\Controllers\ProfileInformationController; // プロフィール情報更新コントローラが必要に応じて
use Laravel\Fortify\Http\Controllers\RegisteredUserController as FortifyRegisteredUserController; // Fortifyのコントローラと名前が衝突しないようにエイリアス
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController as FortifyAuthenticatedSessionController; // Fortifyのコントローラと名前が衝突しないようにエイリアス
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController; // メール認証再送コントローラ



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
Route::middleware('guest')->group(function () {
    // PG01 会員登録画面（一般ユーザー）
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    // PG01 会員登録処理（一般ユーザー） - ★この行は削除しました★
    // Route::post('/register', [RegisteredUserController::class, 'store']); // FortifyがFeatures::registration()で自動で登録します
    // FortifyのFeatures::registration()が有効な場合、
    // POST /register ルートはFortify自身のコントローラ（Laravel\Fortify\Http\Controllers\RegisteredUserController@store）
    // によって処理されます。ユーザー作成のロジックはApp\Actions\Fortify\CreateNewUserに記述します。

    // PG02 ログイン画面（一般ユーザー）
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    // POST /login ルートはFortifyのFeatures::login()が有効な場合に自動で登録されます。
    // その際、FortifyAuthenticatedSessionController の store メソッドが使用されます。
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

// ★★★ メール認証再送ルート ★★★
// 機能要件 FN012 に準拠するため、このルートは必須です。
Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, '__invoke'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.resend');
// ★★★ ここまで ★★★


// --- 管理者認証関連ルート ---

Route::middleware('guest')->group(function () {
    // PG07 ログイン画面（管理者）
    Route::get('/admin/login', [AdminAuthenticatedSessionController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthenticatedSessionController::class, 'store']);
});

// ログアウト (管理者)
Route::middleware(['auth', 'can:admin-access'])->group(function () {
    Route::post('/admin/logout', [AdminAuthenticatedSessionController::class, 'destroy'])->name('admin.logout');
});


// --- 一般ユーザー向け機能ルート ---
Route::middleware(['auth'])->group(function () {
    // PG03 勤怠登録画面（一般ユーザー）
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');
    // 出勤打刻処理
    Route::post('/attendance/checkin', [AttendanceController::class, 'checkIn'])->name('attendance.checkin');
    // 休憩開始処理
    Route::post('/attendance/breakin', [AttendanceController::class, 'breakIn'])->name('attendance.breakin');
    // 休憩終了処理
    Route::post('/attendance/breakout', [AttendanceController::class, 'breakOut'])->name('attendance.breakout');
    // 退勤打刻処理
    Route::post('/attendance/checkout', [AttendanceController::class, 'checkOut'])->name('attendance.checkout');

    // PG04 勤怠一覧画面（一般ユーザー）
    Route::get('/attendance/list/{month?}', [AttendanceController::class, 'list'])->name('attendance.list');

    // PG05 勤怠詳細画面（一般ユーザー）
    Route::get('/attendance/{id}', [AttendanceController::class, 'show'])->name('attendance.show');

    // ★★★ 勤怠修正申請処理（一般ユーザー）のルートをApplicationControllerに移動 ★★★
    // 既存のAttendanceControllerのルートを削除
    // Route::post('/attendance/{id}/request-correction', [AttendanceController::class, 'requestCorrection'])->name('attendance.request-correction');
    // ApplicationControllerの新しいルートを追加 (FN030 修正申請機能)
    Route::post('/attendance/{attendance_id}/correction-request', [ApplicationController::class, 'storeCorrectionRequest'])->name('application.storeCorrectionRequest');
    // ★★★ ここまで ★★★

    // PG06 申請一覧画面（一般ユーザー）
    Route::get('/stamp_correction_request/list', [ApplicationController::class, 'index'])->name('stamp_correction_request.list');
    // 申請詳細画面（一般ユーザー）- 修正不可
    Route::get('/stamp_correction_request/{id}', [ApplicationController::class, 'show'])->name('stamp_correction_request.show');


    // Fortifyメール認証後のリダイレクト先 (仮のルート)
    // 要件FN005「会員登録直後、打刻画面に遷移すること（メール認証を実装しなかった場合）」
    // と矛盾する可能性あり。メール認証後の遷移先を明確にする
    Route::get('/mypage', function() {
        return redirect()->route('attendance'); // メール認証後、打刻画面へリダイレクト
    })->middleware('verified')->name('mypage');
});


// --- 管理者向け機能ルート ---
Route::middleware(['auth', 'can:admin-access'])->prefix('admin')->name('admin.')->group(function () {
    // 管理者ダッシュボード（デフォルト）
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    // PG08 勤怠一覧画面（管理者）
    Route::get('/attendance/list/{date?}', [AdminAttendanceController::class, 'listDaily'])->name('attendance.list');

    // PG09 勤怠詳細画面（管理者）
    // 設計書に合わせてパスを /attendance/{id} に変更。（admin prefixがあるので /admin/attendance/{id} となる）
    Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('attendance.show');
    Route::post('/attendance/{id}/update', [AdminAttendanceController::class, 'update'])->name('attendance.update');

    // PG10 スタッフ一覧画面（管理者）
    Route::get('/staff/list', [AdminStaffController::class, 'index'])->name('staff.list');

    // PG11 スタッフ別勤怠一覧画面（管理者）
    Route::get('/attendance/staff/{id}/{month?}', [AdminStaffController::class, 'attendanceList'])->name('staff.attendance');
    // CSV出力機能
    Route::get('/attendance/staff/{id}/{month}/csv', [AdminStaffController::class, 'exportCsv'])->name('staff.attendance.csv');

    // PG12 申請一覧画面（管理者）
    Route::get('/stamp_correction_request/list', [AdminApplicationController::class, 'index'])->name('stamp_correction_request.list');

    // PG13 修正申請承認画面（管理者）
    Route::get('/stamp_correction_request/approve/{id}', [AdminApplicationController::class, 'showApproveForm'])->name('stamp_correction_request.approve.show');
    Route::post('/stamp_correction_request/approve/{id}', [AdminApplicationController::class, 'approve'])->name('stamp_correction_request.approve');
});
