<?php

namespace App\Http\Controllers; // 正しいネームスペース

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use App\Models\User; // storeメソッドを削除するため不要
// use App\Models\Role; // storeメソッドを削除するため不要
// use Illuminate\Support\Facades\Hash; // storeメソッドを削除するため不要
// use Illuminate\Auth\Events\Registered; // storeメソッドを削除するため不要
use App\Http\Requests\RegisterUserRequest; // RegisterUserRequestはcreateメソッドで使用されていないため、もしcreateメソッドでバリデーションを行わないなら不要です

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

}
