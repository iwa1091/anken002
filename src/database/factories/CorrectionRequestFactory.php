<?php

namespace Database\Factories;

use App\Models\CorrectionRequest;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class CorrectionRequestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CorrectionRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // ここではファクトリのインスタンスを返すことで、関連するモデルのIDが自動的に設定されるようにする
        // テストで明示的にIDが渡された場合は、そちらが優先される
        return [
            'user_id' => User::factory(),
            'attendance_id' => Attendance::factory(),
            'requested_check_in_time' => $this->faker->dateTimeThisMonth(),
            'requested_check_out_time' => $this->faker->dateTimeThisMonth(),
            'requested_breaks' => json_encode([]), // JSONとして保存されるように空の配列をエンコード
            'reason' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
        ];
    }

    /**
     * Indicate that the request is for a punch-in error.
     *
     * @return static
     */
    public function punchInError(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => '打刻ミス',
            'reason' => '出勤打刻を押し忘れました。',
            'requested_check_in_time' => Carbon::parse($attributes['attendance_id'] ? Attendance::find($attributes['attendance_id'])->date . ' 09:00:00' : '09:00:00'),
            'requested_check_out_time' => $attributes['requested_check_out_time'] ?? null,
            'requested_breaks' => json_encode([]),
        ]);
    }

    /**
     * Indicate that the request is for a break time correction.
     *
     * @return static
     */
    public function breakTimeCorrection(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => '休憩時間修正',
            'reason' => '休憩時間が正しく記録されていませんでした。',
            'requested_check_in_time' => $attributes['requested_check_in_time'] ?? null,
            'requested_check_out_time' => $attributes['requested_check_out_time'] ?? null,
            'requested_breaks' => json_encode([
                ['start' => '12:00', 'end' => '12:45'],
                ['start' => '15:00', 'end' => '15:15'],
            ]),
        ]);
    }

    /**
     * Indicate that the request status is pending.
     *
     * @return static
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the request status is approved.
     *
     * @return static
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    /**
     * Indicate that the request status is rejected.
     *
     * @return static
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }

    /**
     * Associate the correction request with a specific Attendance record.
     *
     * @param Attendance $attendance
     * @return static
     */
    public function forAttendance(Attendance $attendance): static
    {
        return $this->state(fn (array $attributes) => [
            'attendance_id' => $attendance->id,
            'user_id' => $attendance->user_id,
        ]);
    }
}
