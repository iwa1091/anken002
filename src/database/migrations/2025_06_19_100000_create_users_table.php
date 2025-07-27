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
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // 主キー
            $table->string('name'); // ユーザー名
            $table->string('email')->unique(); // メールアドレス（ユニーク）
            $table->timestamp('email_verified_at')->nullable(); // メール認証日時
            $table->string('password'); // パスワード

            // ★修正点1: nullable() を追加し、ロールが未設定でもユーザーを作成できるようにする
            // ★修正点2: onDelete('cascade') を onDelete('set null') に変更
            //          これにより、ロールが削除されても、関連するユーザーは削除されず、
            //          role_id が NULL に設定されるようになります。
            $table->foreignId('role_id')->nullable()->constrained()->onDelete('set null');

            $table->rememberToken(); // remember_token 用
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
