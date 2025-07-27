<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User; // ユーザーモデルをインポート
use App\Http\Requests\Admin\Auth\AdminLoginRequest; // AdminLoginRequest をインポート
use Illuminate\Support\Facades\Log; // Logファサードをインポート (今回は残すが、不要なら削除可)

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the admin login view.
     * 管理者ログインビューを表示します。
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.auth.login'); // 管理者用のログインビューを表示
    }

    /**
     * Handle an incoming admin authentication request.
     * 管理者認証リクエストを処理します。
     *
     * @param  \App\Http\Requests\Admin\Auth\AdminLoginRequest  $request // AdminLoginRequestを使用
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(AdminLoginRequest $request) // 型ヒントをAdminLoginRequestに変更
    {
        // Log::info('DEBUG: AdminLoginRequest store method started.'); // この行を削除

        // バリデーションと認証はAdminLoginRequestクラスのauthenticate()メソッドによって自動的に行われる
        // もしバリデーションや認証が失敗した場合、自動的に適切なエラーレスポンスが返される
        $request->authenticate(); // 認証処理を実行

        // 認証成功後の処理
        $request->session()->regenerate();

        // 認証成功直後の状態をログに出力 (デバッグログを削除)
        // if (Auth::guard('admin')->check()) {
        //     $user = Auth::guard('admin')->user();
        //     Log::info('DEBUG: Admin authenticated successfully. User ID: ' . $user->id . ', Email: ' . $user->email);
        //     Log::info('DEBUG: User is admin (via isAdmin() method): ' . ($user->isAdmin() ? 'true' : 'false'));
        // } else {
        //     Log::warning('DEBUG: Admin authentication failed or guard check returned false after authenticate().');
        // }

        // ログイン成功後の管理者勤怠一覧へのリダイレクト
        return redirect()->intended(route('admin.attendance.list'));
    }

    /**
     * Destroy an authenticated admin session.
     * 認証セッションを破棄します（管理者ログアウト）。
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        // 管理者ガード 'admin' を使用してログアウトします。
        Auth::guard('admin')->logout();

        $request->session()->invalidate(); // セッションを無効化

        $request->session()->regenerateToken(); // CSRFトークンを再生成

        // ログアウト後のリダイレクト先（管理者ログインページ）
        return redirect()->route('admin.login');
    }
}
