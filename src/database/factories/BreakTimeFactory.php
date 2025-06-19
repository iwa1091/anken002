<?php

namespace Database\Factories;

use App\Models\BreakTime;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon; // 日付・時刻操作のためにCarbonを使用

class BreakTimeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     * 対応するモデルの名前
     *
     * @var string
     */
    protected $model = BreakTime::class;

    /**
     * Define the model's default state.
     * モデルのデフォルトの状態を定義
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 休憩開始時刻と終了時刻を生成
        // まず基準となる日付を設定
        $date = Carbon::today()->subDays(rand(0, 30)); // 過去30日間のいずれかの日

        // 出勤開始時刻に近い、妥当な休憩開始時刻を生成
        $startHour = rand(12, 16); // 12時から16時の間に開始
        $startMinute = rand(0, 59);
        $breakStartTime = $date->copy()->setTime($startHour, $startMinute, 0);

        // 休憩終了時刻は開始時刻から30分〜1時間半後とする
        $breakEndTime = $breakStartTime->copy()->addMinutes(rand(30, 90));

        return [
            // attendance_id は関連するAttendanceモデルのファクトリを利用して生成
            // または、既存のAttendance IDを渡すことも可能です。
            'attendance_id' => Attendance::factory(),
            'break_start_time' => $breakStartTime,
            'break_end_time' => $breakEndTime,
        ];
    }

    /**
     * 特定のattendance_idを持つ状態
     *
     * @param int $attendanceId
     * @return static
     */
    public function forAttendance(int $attendanceId): static
    {
        return $this->state(fn (array $attributes) => [
            'attendance_id' => $attendanceId,
        ]);
    }
}

