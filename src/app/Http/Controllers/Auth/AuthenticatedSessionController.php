<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException; // バリデーション例外をスローするために必要

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     * ログインビューを表示します。
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.login'); // Fortifyのデフォルトログインビューを表示
    }

    /**
     * Handle an incoming authentication request.
     * 認証リクエストを処理します。
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        // ログイン試行のバリデーション
        // FortifyのLoginRequestを使わないため、ここで直接バリデーションルールを定義します。
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // 'web'ガードを使用して認証を試みる
        // remember me 機能も考慮します。
        if (! Auth::guard('web')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            // 認証失敗の場合、バリデーション例外をスロー
            // これにより、ログインフォームにエラーメッセージが自動的に表示されます。
            throw ValidationException::withMessages([
                'email' => __('auth.failed'), // Laravelのデフォルト認証失敗メッセージを使用
            ]);
        }

        // 認証成功後のセッション再生成
        // セキュリティのために、ログイン成功時にセッションIDを新しく生成します。
        $request->session()->regenerate();

        // ログイン成功後のリダイレクト先
        // RouteServiceProvider::HOME は通常 /dashboard を指します。
        // intended()メソッドは、ユーザーがログイン前にアクセスしようとしていたURLがあればそこへ、
        // なければ指定されたデフォルトのURLへリダイレクトします。
        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     * 認証セッションを破棄します（ログアウト）。
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        // ユーザーをログアウトさせる
        Auth::guard('web')->logout();

        // セッションを無効化する
        // 現在のセッションデータを全て破棄します。
        $request->session()->invalidate();

        // CSRFトークンを再生成する
        // セキュリティのために、セッション破棄後に新しいCSRFトークンを生成します。
        $request->session()->regenerateToken();

        // ログアウト後のリダイレクト先
        // ユーザーをログインページにリダイレクトします。
        return redirect()->route('login');
    }
}
