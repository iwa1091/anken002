<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        // 1. UsersTableSeeder:
        //    rolesテーブルに依存しますが、UsersTableSeeder内でRole::firstOrCreate()
        //    を使用してadminとstaffロールを作成するため、rolesテーブルの別途シーダーは必須ではありません。
        //    最初にユーザーと基本的なロールを投入します。
        $this->call(UsersTableSeeder::class);

        // 2. AttendancesTableSeeder:
        //    ユーザーに紐づく勤怠データを投入します。UsersTableSeederの後に実行する必要があります。
        $this->call(AttendancesTableSeeder::class);

        // 3. BreakTimesTableSeeder:
        //    勤怠データに紐づく休憩データを投入します。AttendancesTableSeederの後に実行する必要があります。
        $this->call(BreakTimesTableSeeder::class);

        // 4. CorrectionRequestsTableSeeder:
        //    ユーザーと勤怠データに紐づく修正申請データを投入します。
        //    UsersTableSeederとAttendancesTableSeederの後に実行する必要があります。
        $this->call(CorrectionRequestsTableSeeder::class);
    }
}
