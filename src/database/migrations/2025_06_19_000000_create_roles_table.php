<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * マイグレーションを実行します。
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id(); // 主キー (unsigned bigint)
            $table->string('name', 50)->unique(); // ロール名 (例: admin, staff) - ユニークかつ必須
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     * マイグレーションを元に戻します。
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
