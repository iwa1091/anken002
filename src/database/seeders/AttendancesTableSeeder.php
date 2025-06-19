<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Role; // Userモデル作成時にRoleも必要になる場合があるため
use Illuminate\Support\Facades\Hash; // User作成時にパスワードをハッシュ化するため
use Carbon\Carbon;

class AttendancesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 開発・テスト用にユーザーとロールが存在することを確認（または作成）
        // 通常はUsersTableSeederが先に実行されていることを想定しますが、
        // このシーダー単独でテストしたい場合のためにfallbackを用意します。
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        $user1 = User::firstOrCreate(
            ['email' => 'staff_user1@example.com'],
            [
                'name' => 'テストスタッフ１',
                'password' => Hash::make('password'),
                'role_id' => $staffRole->id,
            ]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'staff_user2@example.com'],
            [
                'name' => 'テストスタッフ２',
                'password' => Hash::make('password'),
                'role_id' => $staffRole->id,
            ]
        );

        $user3 = User::firstOrCreate(
            ['email' => 'admin_user1@example.com'],
            [
                'name' => 'テスト管理者１',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
            ]
        );


        // --- ダミー勤怠データの作成 ---

        // ユーザー1の過去3日間の勤怠データ
        // 1. 2日前：出退勤済み、休憩あり
        Attendance::firstOrCreate(
            [
                'user_id' => $user1->id,
                'date' => Carbon::today()->subDays(2)->toDateString(),
            ],
            [
                'check_in_time' => Carbon::today()->subDays(2)->setTime(9, 0, 0),
                'check_out_time' => Carbon::today()->subDays(2)->setTime(18, 0, 0),
                'status' => '退勤済',
                'remarks' => '通常勤務（休憩1時間）',
            ]
        );
        // この勤怠に紐づく休憩データはBreakTimesTableSeederで作成することを想定

        // 2. 1日前：出退勤済み、休憩なし
        Attendance::firstOrCreate(
            [
                'user_id' => $user1->id,
                'date' => Carbon::yesterday()->toDateString(),
            ],
            [
                'check_in_time' => Carbon::yesterday()->setTime(9, 30, 0),
                'check_out_time' => Carbon::yesterday()->setTime(17, 30, 0),
                'status' => '退勤済',
                'remarks' => '短時間勤務',
            ]
        );

        // 3. 今日：出勤中（退勤なし）
        Attendance::firstOrCreate(
            [
                'user_id' => $user1->id,
                'date' => Carbon::today()->toDateString(),
            ],
            [
                'check_in_time' => Carbon::now()->subHours(4), // 4時間前
                'check_out_time' => null, // 退勤はまだ
                'status' => '出勤中',
                'remarks' => '現在の勤務',
            ]
        );

        // ユーザー2の勤怠データ
        // 4. 3日前：出退勤済み、休憩あり（別ユーザー）
        Attendance::firstOrCreate(
            [
                'user_id' => $user2->id,
                'date' => Carbon::today()->subDays(3)->toDateString(),
            ],
            [
                'check_in_time' => Carbon::today()->subDays(3)->setTime(8, 45, 0),
                'check_out_time' => Carbon::today()->subDays(3)->setTime(17, 45, 0),
                'status' => '退勤済',
                'remarks' => 'ユーザー2の通常勤務',
            ]
        );

        // ユーザー3（管理者）の勤怠データ
        // 管理者も打刻する想定であれば追加
        // 5. 1日前：出退勤済み
        Attendance::firstOrCreate(
            [
                'user_id' => $user3->id,
                'date' => Carbon::yesterday()->toDateString(),
            ],
            [
                'check_in_time' => Carbon::yesterday()->setTime(10, 0, 0),
                'check_out_time' => Carbon::yesterday()->setTime(19, 0, 0),
                'status' => '退勤済',
                'remarks' => '管理者テスト勤務',
            ]
        );

        // 必要に応じて、さらに様々な状態の勤怠データを追加できます。
        // 例：
        // Attendance::factory()->count(10)->create(); // ファクトリを使ってさらに多くのデータ
    }
}
