<?php

namespace App\Http\Controllers; // 正しいネームスペース

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use App\Models\User; // storeメソッドを削除するため不要
// use App\Models\Role; // storeメソッドを削除するため不要
// use Illuminate\Support\Facades\Hash; // storeメソッドを削除するため不要
// use Illuminate\Auth\Events\Registered; // storeメソッドを削除するため不要

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     * 会員登録画面を表示します。
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.register'); // Fortifyのデフォルト登録ビューを表示
    }

    // ★★★ store メソッドは削除します。FortifyがPOST /register を処理します。 ★★★
    // /**
    //  * Handle an incoming authentication request.
    //  * 認証リクエストを処理します。
    //  *
    //  * @param  \Illuminate\Http\Request  $request
    //  * @return \Illuminate\Http\RedirectResponse
    //  * @throws \Illuminate\Validation\ValidationException
    //  */
    // public function store(Request $request)
    // {
    //     // Fortifyの登録パイプラインを呼び出すため、
    //     // ここでのバリデーションとUser::createのロジックは不要です。
    //     // FortifyはCreateNewUserアクションでこれらの処理を行います。

    //     // 登録後のリダイレクト先のみを制御します。
    //     // FortifyがMustVerifyEmailインターフェースを検知し、
    //     // ユーザーが未認証であれば自動的にverification.noticeへリダイレクトします。
    //     return redirect()->route('verification.notice')->with('status', '登録が完了しました。メールをご確認ください。');
    // }
}
