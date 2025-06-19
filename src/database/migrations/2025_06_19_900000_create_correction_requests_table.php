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
        Schema::create('correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->onDelete('cascade'); // 修正対象の勤怠データへの外部キー
            $table->foreignId('user_id')->constrained()->onDelete('cascade');     // 申請者（ユーザー）への外部キー
            $table->string('type', 50)->comment('修正の種類（例: 打刻ミス、削除希望など）'); // stringタイプに修正
            $table->dateTime('requested_check_in_time')->nullable()->comment('修正希望の出勤時刻');
            $table->dateTime('requested_check_out_time')->nullable()->comment('修正希望の退勤時刻');
            $table->json('requested_breaks')->nullable()->comment('修正希望の休憩情報（JSON形式）'); // JSONタイプ
            $table->text('reason')->comment('修正理由'); // textタイプに修正
            $table->string('status', 50)->default('pending')->comment('申請の状態（承認待ち／承認済／却下など）'); // stringタイプに修正、デフォルト値設定
            $table->timestamps(); // created_at と updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('correction_requests');
    }
};

