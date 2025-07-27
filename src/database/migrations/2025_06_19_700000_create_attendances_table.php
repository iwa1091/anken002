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
            $table->id(); // Primary key

            // Add user_id column and set foreign key constraint to users table
            // If a user is deleted, associated attendance data is also deleted
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->date('date')->comment('勤務日'); // Work date

            // Set unique constraint with combination of user_id and date
            // To prevent the same user from having multiple attendance records on the same day
            $table->unique(['user_id', 'date']);

            $table->dateTime('check_in_time')->nullable()->comment('出勤時刻'); // Check-in time (null if not punched)
            $table->dateTime('check_out_time')->nullable()->comment('退勤時刻'); // Check-out time (null if not punched)

            // Add break start time and end time
            $table->timestamp('break_start_time')->nullable()->comment('休憩開始時刻');
            $table->timestamp('break_end_time')->nullable()->comment('休憩終了時刻');

            // Add break time (in seconds) and working time (in seconds)
            // unsignedInteger is an unsigned integer type that does not allow negative values, suitable for time calculation
            $table->unsignedInteger('break_time')->default(0)->comment('休憩時間（秒）');
            $table->unsignedInteger('working_time')->default(0)->comment('勤務時間（秒）');

            // Status indicating work status (e.g., Off Duty, Working, On Break, Clocked Out)
            // String type, default to '勤務外'
            $table->string('status', 50)->default('勤務外')->comment('勤務状況');

            $table->text('remarks')->nullable()->comment('備考'); // Remarks/memo field (text type for longer notes)

            $table->timestamps(); // created_at and updated_at
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
