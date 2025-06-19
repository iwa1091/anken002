<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id(); // 主キー

            // user_idカラムを追加し、usersテーブルへの外部キー制約を設定
            // ユーザーが削除されたら関連する勤怠データも削除
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->date('date')->comment('勤務日'); // 勤務日

            // user_id と date の組み合わせでユニーク制約を設定
            // 同じユーザーが同じ日に複数の勤怠レコードを持たないようにする
            $table->unique(['user_id', 'date']);

            $table->dateTime('check_in_time')->nullable()->comment('出勤時刻'); // 出勤時刻（打刻がない場合はnull）
            $table->dateTime('check_out_time')->nullable()->comment('退勤時刻'); // 退勤時刻（打刻がない場合はnull）

            // 勤務状況を示すステータス（例: 勤務外, 出勤中, 休憩中, 退勤済）
            // stringタイプで、デフォルト値を '勤務外' に設定
            $table->string('status', 50)->default('勤務外')->comment('勤務状況');

            $table->text('remarks')->nullable()->comment('備考'); // 備考・メモ欄（長文を考慮しtextタイプ）

            $table->timestamps(); // created_at と updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
