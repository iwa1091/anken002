<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers; // ★★★ この行が正しいか再確認しました ★★★

class CreateNewUser implements CreatesNewUsers // ★★★ この行が正しいか再確認しました ★★★
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     * 新しく登録されるユーザーをバリデーションし、作成します。
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        // ここで 'staff' ロールを取得または作成します。
        // これにより、データベースに 'staff' ロールが存在しない場合でも、
        // ユーザー登録時に自動的に作成され、エラーを防ぎます。
        $staffRole = Role::firstOrCreate(['name' => 'staff']);

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'role_id' => $staffRole->id, // 取得した'staff'ロールのIDを割り当てる
        ]);
    }
}
