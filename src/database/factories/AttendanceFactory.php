<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Attendance::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), 
            'check_in_time' => Carbon::today()->setTime(9, 0),
            'check_out_time' => null,
            'date' => Carbon::today()->toDateString(),
            'status' => '勤務中',
        ];
    }

    /**
     * Indicate that the model is checked out.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function checkedOut(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'check_out_time' => Carbon::today()->setTime(18, 0),
                'status' => '勤務外',
            ];
        });
    }

    /**
     * Indicate that the user is on a break.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function onBreak(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                // 修正: Carbon::now() -> Carbon::today()->setTime(12, 0)
                'break_start_time' => Carbon::today()->setTime(12, 0),
                'status' => '休憩中',
            ];
        });
    }
    
    /**
     * Indicate that the user has ended a break.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function breakEnded(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                // 修正: Carbon::now() -> Carbon::today()->setTime(13, 0)
                'break_end_time' => Carbon::today()->setTime(13, 0),
                'status' => '勤務中',
            ];
        });
    }
}
