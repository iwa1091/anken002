<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CorrectionRequest;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon; // 日付・時刻操作のためにCarbonを使用

class CorrectionRequestsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 開発・テスト用にユーザーと勤怠データが存在することを確認（または作成）
        // UsersTableSeederが先に実行されていることを想定
        $user1 = User::firstOrCreate([
            'email' => 'user1@example.com'
        ], [
            'name' => 'テストユーザー1',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role_id' => \App\Models\Role::firstOrCreate(['name' => 'staff'])->id,
        ]);

        $user2 = User::firstOrCreate([
            'email' => 'user2@example.com'
        ], [
            'name' => 'テストユーザー2',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role_id' => \App\Models\Role::firstOrCreate(['name' => 'staff'])->id,
        ]);

        // 勤怠データの取得または作成
        // 修正申請の対象となる勤怠データが必要です
        $attendance1 = Attendance::firstOrCreate([
            'user_id' => $user1->id,
            'date' => Carbon::today()->toDateString(), // 今日の勤怠
        ], [
            'check_in_time' => Carbon::now()->subHours(8), // 8時間前
            'check_out_time' => Carbon::now(),
            'status' => '退勤済', // Example status
            'remarks' => '通常の勤務',
        ]);

        $attendance2 = Attendance::firstOrCreate([
            'user_id' => $user2->id,
            'date' => Carbon::yesterday()->toDateString(), // 昨日の勤怠
        ], [
            'check_in_time' => Carbon::yesterday()->addHours(9),
            'check_out_time' => Carbon::yesterday()->addHours(18),
            'status' => '退勤済',
            'remarks' => '昨日の勤務',
        ]);

        // 修正申請データの作成
        // 1. 承認待ちの打刻ミス申請
        CorrectionRequest::create([
            'attendance_id' => $attendance1->id,
            'user_id' => $user1->id,
            'type' => '打刻ミス',
            // 修正: CarbonオブジェクトのtoDateString()を使用して日付部分のみを取得
            'requested_check_in_time' => Carbon::parse($attendance1->date->toDateString() . ' 09:00:00'), // 9:00 に修正希望
            'requested_check_out_time' => Carbon::parse($attendance1->date->toDateString() . ' 18:00:00'), // 18:00 に修正希望
            'requested_breaks' => [ // 休憩をJSON配列で指定
                ['start' => '12:00', 'end' => '13:00'],
            ],
            'reason' => '出勤打刻を押し忘れました。',
            'status' => 'pending', // 承認待ち
        ]);

        // 2. 承認済みの休憩時間修正申請
        CorrectionRequest::create([
            'attendance_id' => $attendance2->id,
            'user_id' => $user2->id,
            'type' => '休憩時間修正',
            'requested_check_in_time' => $attendance2->check_in_time, // 変更なし
            'requested_check_out_time' => $attendance2->check_out_time, // 変更なし
            'requested_breaks' => [ // 休憩をJSON配列で指定
                ['start' => '12:00', 'end' => '12:45'], // 休憩を短縮
                ['start' => '15:00', 'end' => '15:15'], // 追加休憩
            ],
            'reason' => '休憩時間が正しく記録されていませんでした。',
            'status' => 'approved', // 承認済み
        ]);

        // 3. 却下された申請（例として）
        CorrectionRequest::create([
            'attendance_id' => $attendance1->id,
            'user_id' => $user1->id,
            'type' => '退勤打刻修正',
            'requested_check_in_time' => $attendance1->check_in_time,
            // 修正: CarbonオブジェクトのtoDateString()を使用して日付部分のみを取得
            'requested_check_out_time' => Carbon::parse($attendance1->date->toDateString() . ' 20:00:00'), // 20:00 に修正希望
            'requested_breaks' => [],
            'reason' => '残業分の記録が漏れていました。',
            'status' => 'rejected', // 却下
        ]);
    }
}
