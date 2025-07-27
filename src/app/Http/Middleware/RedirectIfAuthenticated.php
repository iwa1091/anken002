<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response; // Symfony\Component\HttpFoundation\Response を使用

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$guards // string ...$guards に変更
     * @return \Symfony\Component\HttpFoundation\Response // Response を使用
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        // ガードが指定されていない場合、デフォルトで 'web' ガードを使用
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            // 各ガードで認証済みかチェック
            if (Auth::guard($guard)->check()) {
                // 認証済みの場合のリダイレクト先をガードごとに設定
                if ($guard === 'admin') {
                    // 管理者ガードで認証済みの場合、管理者勤怠一覧へリダイレクト
                    return redirect()->route('admin.attendance.list');
                }
                // その他のガード（例: 'web'）で認証済みの場合、デフォルトのホームへリダイレクト
                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}
