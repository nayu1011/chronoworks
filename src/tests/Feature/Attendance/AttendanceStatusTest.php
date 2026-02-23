<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ID05_01_勤務外の場合はステータスが勤務外と表示され出勤ボタンが表示される(): void
    {
        $this->setNow();
        $this->loginAsUser();

        $response = $this->get(route('attendance.index'));

        $response->assertStatus(200);

        // ステータスバッジ
        $response->assertSee('勤務外', false);

        // ボタン
        $response->assertSee('出勤', false);
    }

    /** @test */
    public function ID05_02_出勤中の場合はステータスが出勤中と表示される(): void
    {
        $this->setNow();
        $user = $this->loginAsUser();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'start_time' => Carbon::now()->subHours(1),
            'end_time' => null,
        ]);

        $response = $this->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('出勤中', false);
        $response->assertSee('休憩入', false);
        $response->assertSee('退勤', false);
    }

    /** @test */
    public function ID05_03_休憩中の場合はステータスが休憩中と表示される(): void
    {
        $this->setNow();
        $user = $this->loginAsUser();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'start_time' => Carbon::now()->subHours(1),
            'end_time' => null,
        ]);

        AttendanceBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::now()->subMinutes(10),
            'end_time' => null,
        ]);

        $response = $this->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('休憩中', false);
        $response->assertSee('休憩戻', false);
    }

    /** @test */
    public function ID05_04_退勤済の場合はステータスが退勤済と表示される(): void
    {
        $this->setNow();
        $user = $this->loginAsUser();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'start_time' => Carbon::now()->subHours(8),
            'end_time' => Carbon::now()->subHours(1),
        ]);

        $response = $this->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('退勤済', false);

        $response->assertSee('お疲れ様でした。', false);
    }
}
