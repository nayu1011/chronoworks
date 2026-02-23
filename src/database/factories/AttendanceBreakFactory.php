<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceBreak>
 */
class AttendanceBreakFactory extends Factory
{
    protected $model = AttendanceBreak::class;

    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'start_time' => Carbon::now()->subMinutes(10),
            'end_time' => Carbon::now()->subMinutes(5),
        ];
    }

    public function resting(): static
    {
        return $this->state(function () {
            return [
                'start_time' => Carbon::now()->subMinutes(10),
                'end_time' => null,
            ];
        });
    }
}
