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
        Schema::table('correction_requests', function (Blueprint $table) {
            // type カラムが存在する場合に削除
            if (Schema::hasColumn('correction_requests', 'type')) {
                $table->dropColumn('type');
            }
        });
    }

    /**
     * Reverse the migrations.
     * マイグレーションを元に戻す際に、type カラムを再追加します。
     * ただし、元のカラム定義（nullableかどうか、デフォルト値など）に合わせてください。
     */
    public function down(): void
    {
        Schema::table('correction_requests', function (Blueprint $table) {
            // type カラムを再追加 (元の定義に合わせてください)
            // 例: $table->string('type')->nullable()->after('user_id');
            // もし元々 not nullable だった場合は nullable() を削除
            $table->string('type')->nullable()->after('user_id'); // 例: user_id の後に再追加
        });
    }
};
