<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role; // Roleモデルをインポート

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ロールが存在しない場合のみ作成
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'staff']);
        // 必要に応じて他のロールも追加
        // Role::firstOrCreate(['name' => 'manager']);
    }
}
