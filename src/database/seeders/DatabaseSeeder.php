<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * アプリケーションのデータベースをシードします。
     */
    public function run(): void
    {
        // 依存関係に基づいてシーダーを呼び出す順序を決定します。
        //
        // 1. RolesTableSeeder:
        //    必ず最初にロールを投入します。これにより、他のシーダーやアプリケーションが
        //    ロールに依存する場合でも、ロールが確実に存在することを保証します。
        $this->call(RolesTableSeeder::class);

        // 2. UsersTableSeeder:
        //    ユーザーと基本的なロールを投入します。rolesテーブルが存在することに依存します。
        $this->call(UsersTableSeeder::class);

        // 3. AttendancesTableSeeder:
        //    ユーザーに紐づく勤怠データを投入します。UsersTableSeederの後に実行する必要があります。
        $this->call(AttendancesTableSeeder::class);

        // 4. BreakTimesTableSeeder:
        //    勤怠データに紐づく休憩データを投入します。AttendancesTableSeederの後に実行する必要があります。
        $this->call(BreakTimesTableSeeder::class);

        // 5. CorrectionRequestsTableSeeder:
        //    ユーザーと勤怠データに紐づく修正申請データを投入します。
        //    UsersTableSeederとAttendancesTableSeederの後に実行する必要があります。
        $this->call(CorrectionRequestsTableSeeder::class);
    }
}
