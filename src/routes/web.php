<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\Admin\ApplicationController as AdminApplicationController;
use Laravel\Fortify\Http\Controllers\EmailVerificationPromptController;
use Laravel\Fortify\Http\Controllers\VerifyEmailController;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;
use Illuminate\Support\Facades\Auth;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- 認証関連ルート (Fortifyが提供するルートに準拠) ---

// 一般ユーザー
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
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

// ★★★ 修正箇所 ★★★
// メール認証再送ルート
Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.resend');


// --- 管理者認証関連ルート ---

// PG07 ログイン画面（管理者） - GETリクエストのみguestミドルウェアを適用
Route::get('/admin/login', [AdminAuthenticatedSessionController::class, 'create'])->name('admin.login')->middleware('guest:admin');
// ログイン処理（POSTリクエスト） - guestミドルウェアは適用しない
Route::post('/admin/login', [AdminAuthenticatedSessionController::class, 'store']);


// ログアウト (管理者)
Route::middleware(['auth:admin'])->group(function () {
    Route::post('/admin/logout', [AdminAuthenticatedSessionController::class, 'destroy'])->name('admin.logout');
});


// --- 一般ユーザー向け機能ルート ---
Route::middleware(['auth'])->group(function () {
    // 一般ユーザー向けのダッシュボードルート
    Route::get('/dashboard', function () {
        return redirect()->route('attendance');
    })->name('dashboard');

    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');
    Route::post('/attendance/checkin', [AttendanceController::class, 'checkIn'])->name('attendance.checkin');
    Route::post('/attendance/breakin', [AttendanceController::class, 'breakIn'])->name('attendance.breakin');
    Route::post('/attendance/breakout', [AttendanceController::class, 'breakOut'])->name('attendance.breakout');
    Route::post('/attendance/checkout', [AttendanceController::class, 'checkOut'])->name('attendance.checkout');

    Route::get('/attendance/list/{month?}', [AttendanceController::class, 'list'])->name('attendance.list');
    Route::get('/attendance/{id}', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::post('/attendance/{attendance_id}/correction-request', [ApplicationController::class, 'storeCorrectionRequest'])->name('application.storeCorrectionRequest');

    Route::get('/stamp_correction_request/list', [ApplicationController::class, 'index'])->name('stamp_correction_request.list');
    Route::get('/stamp_correction_request/{id}', [ApplicationController::class, 'show'])->name('stamp_correction_request.show');

    // Fortifyメール認証後のリダイレクト先
    Route::get('/mypage', function() {
        return redirect()->route('attendance');
    })->middleware('verified')->name('mypage');
});


// --- 管理者向け機能ルート ---
Route::middleware(['auth:admin', 'can:admin-access'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/attendance/list/{date?}', [AdminAttendanceController::class, 'list'])->name('attendance.list');
    Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('attendance.show');
    Route::get('/attendance/{id}/edit', [AdminAttendanceController::class, 'edit'])->name('attendance.edit');
    Route::put('/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('attendance.update');
    Route::delete('/attendance/{id}', [AdminAttendanceController::class, 'delete'])->name('attendance.delete');
    Route::get('/staff/list', [AdminStaffController::class, 'index'])->name('staff.list');
    Route::get('/attendance/staff/{id}/{month?}', [AdminStaffController::class, 'attendanceList'])->name('staff.attendance');
    Route::get('/attendance/staff/{id}/{month}/csv', [AdminStaffController::class, 'exportCsv'])->name('staff.attendance.csv');
    Route::get('/stamp_correction_request/list', [AdminApplicationController::class, 'index'])->name('stamp_correction_request.list');
    Route::get('/stamp_correction_request/approve/{id}', [AdminApplicationController::class, 'showApproveForm'])->name('stamp_correction_request.approve.show');
    Route::post('/stamp_correction_request/approve/{id}', [AdminApplicationController::class, 'approve'])->name('stamp_correction_request.approve');
});
