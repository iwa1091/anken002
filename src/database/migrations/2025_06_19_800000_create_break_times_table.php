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
        Schema::create('break_times', function (Blueprint $table) {
            $table->id(); // Primary key for the break time record
            $table->foreignId('attendance_id')
                  ->constrained('attendances') // Foreign key to the attendances table
                  ->onDelete('cascade');     // If an attendance record is deleted, related break times are also deleted

            $table->dateTime('break_start_time')->comment('休憩開始時刻'); // Break start time
            $table->dateTime('break_end_time')->nullable()->comment('休憩終了時刻');   // Break end time

            $table->timestamps(); // Adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('break_times');
    }
};

