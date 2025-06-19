<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 必要なロールの取得（または作成）
        // マネージャーロールは使用しないため削除
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);

        // 管理者ユーザーの作成
        User::create([
            'name' => '管理者ユーザー',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'), // パスワードは適宜変更
            'role_id' => $adminRole->id,
        ]);

        // スタッフユーザーの作成
        User::create([
            'name' => 'スタッフ花子',
            'email' => 'staff@example.com',
            'password' => Hash::make('password'),
            'role_id' => $staffRole->id,
        ]);

        // 必要に応じて、追加のスタッフユーザーを作成
        // User::create([
        //     'name' => 'スタッフ一郎',
        //     'email' => 'staff2@example.com',
        //     'password' => Hash::make('password'),
        //     'role_id' => $staffRole->id,
        // ]);
    }
}
