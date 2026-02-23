<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        $date = Carbon::today()->toDateString();

        return [
            'user_id'       => User::factory(),
            'date'          => $date,
            'start_time'    => Carbon::createFromFormat('Y-m-d H:i', "$date 09:00"),
            'end_time'      => null,
            'comment'       => null,
        ];
    }

    public function working(): static
    {
        $date = Carbon::today()->toDateString();

        return $this->state(fn () => [
            'date'       => $date,
            'start_time' => Carbon::createFromFormat('Y-m-d H:i', "{$date} 09:00"),
            'end_time'   => null,
        ]);
    }

    public function finished(): static
    {
        $date = Carbon::today()->toDateString();

        return $this->state(fn () => [
            'start_time' => Carbon::createFromFormat('Y-m-d H:i', "{$date} 09:00"),
            'end_time'   => Carbon::createFromFormat('Y-m-d H:i', "{$date} 18:00"),
        ]);
    }
}
