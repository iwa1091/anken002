<?php

namespace App\Http\Controllers; // ここを App\Http\Controllers に修正

use App\Http\Controllers\Controller;
use App\Models\User; // Userモデルをインポート
use App\Models\Role; // Roleモデルをインポートして、デフォルトロールを割り当てる
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered; // ユーザー登録イベントをトリガー

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     * 会員登録ビューを表示します。
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.register'); // Fortifyのデフォルト登録ビューを表示
    }

    /**
     * Handle an incoming registration request.
     * 会員登録リクエストを処理します。
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        // Fortifyの登録パイプラインを呼び出す
        // 通常、POST /register はFortify内部のCreateNewUserクラスによって処理されます。
        // ここに直接ロジックを記述する場合、Fortifyのデフォルトの挙動をオーバーライドすることになります。
        //
        // 例として、FortifyのCreateNewUserクラスが行う処理を簡略化して示します。
        // 実際にはFortifyの機能に任せるのが一般的です。

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'], // 'confirmed' は password_confirmation が必要
        ]);

        // デフォルトロールとして'staff'を取得または作成
        $staffRole = Role::firstOrCreate(['name' => 'staff']);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $staffRole->id, // 新規登録ユーザーにはデフォルトでstaffロールを付与
        ]);

        event(new Registered($user)); // Registeredイベントを発火 (メール認証などで使用)

        // 登録後に自動ログインさせる場合 (Fortifyのデフォルトの挙動)
        // Auth::login($user);

        // 登録後のリダイレクト先 (通常はログインページまたはダッシュボード)
        return redirect()->route('login')->with('status', '登録が完了しました。ログインしてください。');
    }
}
