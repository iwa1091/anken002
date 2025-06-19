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
     * 対応するモデルの名前
     *
     * @var string
     */
    protected $model = CorrectionRequest::class;

    /**
     * Define the model's default state.
     * モデルのデフォルトの状態を定義
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // ユーザーと勤怠データを関連付けるためにファクトリを使用
        // または、シーダーで作成済みの既存レコードを渡すことも可能
        $user = User::factory()->create(); // ユーザーを自動生成
        $attendance = Attendance::factory()->forUser($user)->create(); // そのユーザーの勤怠を自動生成

        // 修正申請のタイプとステータスの候補
        $types = ['打刻ミス', '休憩時間修正', '出勤日修正', '退勤日修正'];
        $statuses = ['pending', 'approved', 'rejected'];

        // リクエストされた出勤・退勤時刻（null許容、または元の勤怠から少しずらす）
        $requestedCheckInTime = $attendance->check_in_time ? $attendance->check_in_time->copy()->addMinutes(rand(-15, 15)) : null;
        $requestedCheckOutTime = $attendance->check_out_time ? $attendance->check_out_time->copy()->addMinutes(rand(-15, 15)) : null;

        // リクエストされた休憩情報（JSON形式にキャストされる配列）
        $requestedBreaks = [];
        if (rand(0, 1)) { // 50%の確率で休憩修正を追加
            $breakStartTime = Carbon::parse('12:00:00')->addMinutes(rand(-10, 10));
            $breakEndTime = $breakStartTime->copy()->addMinutes(rand(45, 75));
            $requestedBreaks[] = ['start' => $breakStartTime->format('H:i'), 'end' => $breakEndTime->format('H:i')];
        }
        if (rand(0, 1) && !empty($requestedBreaks)) { // 既に休憩があれば、さらに追加
            $breakStartTime = Carbon::parse('15:00:00')->addMinutes(rand(-10, 10));
            $breakEndTime = $breakStartTime->copy()->addMinutes(rand(10, 20));
            $requestedBreaks[] = ['start' => $breakStartTime->format('H:i'), 'end' => $breakEndTime->format('H:i')];
        }


        return [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'type' => $this->faker->randomElement($types),
            'requested_check_in_time' => $requestedCheckInTime,
            'requested_check_out_time' => $requestedCheckOutTime,
            'requested_breaks' => $requestedBreaks, // 配列として渡し、モデルのキャストでJSONに変換
            'reason' => $this->faker->sentence(rand(5, 10)), // 5〜10単語の理由
            'status' => $this->faker->randomElement($statuses),
        ];
    }

    /**
     * Indicate that the request is for a punch-in error.
     * 打刻ミスの申請状態を示す
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
            'requested_breaks' => [],
        ]);
    }

    /**
     * Indicate that the request is for a break time correction.
     * 休憩時間修正の申請状態を示す
     *
     * @return static
     */
    public function breakTimeCorrection(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => '休憩時間修正',
            'reason' => '休憩時間が正しく記録されていませんでした。',
            'requested_check_in_time' => $attributes['requested_check_in_time'] ?? null, // 元の出退勤は変更しない
            'requested_check_out_time' => $attributes['requested_check_out_time'] ?? null, // 元の出退勤は変更しない
            'requested_breaks' => [
                ['start' => '12:00', 'end' => '12:45'],
                ['start' => '15:00', 'end' => '15:15'],
            ],
        ]);
    }

    /**
     * Indicate that the request status is pending.
     * 承認待ちの申請状態を示す
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
     * 承認済みの申請状態を示す
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
     * 却下された申請状態を示す
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
     * 特定の勤怠データに関連付ける
     *
     * @param Attendance $attendance
     * @return static
     */
    public function forAttendance(Attendance $attendance): static
    {
        return $this->state(fn (array $attributes) => [
            'attendance_id' => $attendance->id,
            'user_id' => $attendance->user_id, // 申請者も勤怠データのユーザーに合わせる
        ]);
    }
}
