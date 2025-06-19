<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BreakTime;
use App\Models\Attendance;
use App\Models\User; // Attendanceを作成するためにUserモデルも必要
use App\Models\Role; // Userを作成するためにRoleモデルも必要
use Carbon\Carbon;

class BreakTimesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // BreakTimeを紐付けるためのAttendanceレコードが必要です。
        // ここでは、もし存在しなければシンプルなAttendanceレコードを作成します。
        // 通常はUsersTableSeederとAttendancesTableSeederが先に実行されることを想定します。

        // ユーザーとロールが存在することを確認（なければ作成）
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $user = User::firstOrCreate(
            ['email' => 'staff_user@example.com'],
            [
                'name' => 'シーダーテストスタッフ',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role_id' => $staffRole->id,
            ]
        );

        // 今日の勤怠レコードを取得または作成
        $todayAttendance = Attendance::firstOrCreate(
            [
                'user_id' => $user->id,
                'date' => Carbon::today()->toDateString(),
            ],
            [
                'check_in_time' => Carbon::parse('09:00:00'),
                'check_out_time' => Carbon::parse('18:00:00'),
                'status' => '退勤済',
                'remarks' => 'テスト用勤怠レコード（休憩データ紐付け用）',
            ]
        );

        // 昨日の勤怠レコードを取得または作成
        $yesterdayAttendance = Attendance::firstOrCreate(
            [
                'user_id' => $user->id,
                'date' => Carbon::yesterday()->toDateString(),
            ],
            [
                'check_in_time' => Carbon::yesterday()->setTime(9, 0, 0),
                'check_out_time' => Carbon::yesterday()->setTime(17, 30, 0),
                'status' => '退勤済',
                'remarks' => 'テスト用勤怠レコード（複数休憩データ用）',
            ]
        );

        // 1. 今日の勤怠に休憩時間を追加
        BreakTime::create([
            'attendance_id' => $todayAttendance->id,
            'break_start_time' => Carbon::parse('12:00:00'),
            'break_end_time' => Carbon::parse('13:00:00'),
        ]);

        // 2. 昨日の勤怠に複数の休憩時間を追加
        BreakTime::create([
            'attendance_id' => $yesterdayAttendance->id,
            'break_start_time' => Carbon::yesterday()->setTime(12, 0, 0),
            'break_end_time' => Carbon::yesterday()->setTime(13, 0, 0),
        ]);

        BreakTime::create([
            'attendance_id' => $yesterdayAttendance->id,
            'break_start_time' => Carbon::yesterday()->setTime(15, 0, 0),
            'break_end_time' => Carbon::yesterday()->setTime(15, 15, 0),
        ]);

        // 必要に応じて、さらに休憩データを追加できます。
    }
}
