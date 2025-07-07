<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; // このファイルでは直接使用しないが、Requestを継承するクラスの型ヒントとして残す
use Illuminate\Support\Facades\Auth;
use App\Models\User; // ユーザーモデルをインポート
use App\Http\Requests\Admin\Auth\AdminLoginRequest; // AdminLoginRequest をインポート

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
        // バリデーションと認証はAdminLoginRequestクラスのauthenticate()メソッドによって自動的に行われる
        // もしバリデーションや認証が失敗した場合、自動的に適切なエラーレスポンスが返される

        // AdminLoginRequestのauthenticateメソッドを呼び出して認証を実行
        // ここで認証が失敗すると、throwValidationExceptionにより例外がスローされ、
        // 自動的に前のページにリダイレクトされる
        $request->authenticate();

        // 認証成功後の処理
        // ユーザーが管理者ロールを持っているかのチェックもauthenticate()内で行われる
        $request->session()->regenerate();

        // ログイン成功後の管理者ダッシュボードへのリダイレクト
        return redirect()->intended(route('admin.dashboard'));
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
        Auth::guard('web')->logout(); // 'web' ガードを使用してログアウト

        $request->session()->invalidate(); // セッションを無効化

        $request->session()->regenerateToken(); // CSRFトークンを再生成

        // ログアウト後のリダイレクト先（管理者ログインページ）
        return redirect()->route('admin.login');
    }
}
