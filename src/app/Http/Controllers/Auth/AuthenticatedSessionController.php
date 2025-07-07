<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Fortify; // Fortifyのコントローラを継承するためにインポート

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
     */
    public function store(Request $request)
    {
        // Fortifyの認証パイプラインを呼び出す
        // これはLaravel Fortifyの内部ルーティングによって自動的に処理されます。
        // 通常、このメソッドは直接記述する必要はありませんが、
        // Fortifyのデフォルトの挙動をオーバーライドしたい場合に記述します。
        // ここでは、デフォルトのFortifyのstoreメソッドへのリダイレクトを示します。
        // 実際にはFortifyのルート設定によってPOST /login はFortify内部で処理されます。
        // ログイン試行が成功した場合の処理はFortifyによって制御されます。
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
        $request->session()->invalidate();

        // CSRFトークンを再生成する
        $request->session()->regenerateToken();

        // ログアウト後のリダイレクト先
        return redirect()->route('login'); // トップページまたはログインページへリダイレクト
    }
}
