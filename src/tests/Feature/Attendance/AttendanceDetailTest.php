<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ID10_01_勤怠詳細画面の名前がログインユーザーの氏名になっている(): void
    {
        $this->setNow(2026, 2, 23, 9, 0, 0);

        $user = $this->loginAsUser(null, ['name' => '山田 太郎']);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'start_time' => Carbon::create(2026, 2, 23, 9, 0, 0),
            'end_time' => Carbon::create(2026, 2, 23, 18, 0, 0),
        ]);

        $response = $this->get(route('attendance.detail', $attendance));
        $response->assertStatus(200);

        $response->assertSee('<span class="name">山田 太郎</span>', false);
    }

    /** @test */
    public function ID10_02_勤怠詳細画面の日付が選択した日付になっている(): void
    {
        $this->setNow(2026, 2, 23, 9, 0, 0);

        $user = $this->loginAsUser();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'start_time' => Carbon::create(2026, 2, 23, 9, 0, 0),
            'end_time' => Carbon::create(2026, 2, 23, 18, 0, 0),
        ]);

        $response = $this->get(route('attendance.detail', $attendance));
        $response->assertStatus(200);

        // 日付は span が分割される仕様
        $response->assertSeeInOrder([
            '<span class="date-year">2026年</span>',
            '<span class="date-md">2月23日</span>',
        ], false);

        // hidden の date も一応確認（フォーム仕様の担保）
        $response->assertSee(
            'name="date" value="' . Carbon::today()->toDateString() . '"',
            false
        );
    }

    /** @test */
    public function ID10_03_出勤_退勤の時間がログインユーザーの打刻と一致している(): void
    {
        $this->setNow(2026, 2, 23, 9, 0, 0);

        $user = $this->loginAsUser();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'start_time' => Carbon::create(2026, 2, 23, 9, 0, 0),
            'end_time' => Carbon::create(2026, 2, 23, 18, 0, 0),
        ]);

        $response = $this->get(route('attendance.detail', $attendance));
        $response->assertStatus(200);

        $response->assertSee('value="09:00"', false);
        $response->assertSee('value="18:00"', false);
    }

    /** @test */
    public function ID10_04_休憩の時間がログインユーザーの打刻と一致している(): void
    {
        $this->setNow(2026, 2, 23, 9, 0, 0);

        $user = $this->loginAsUser();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'start_time' => Carbon::create(2026, 2, 23, 9, 0, 0),
            'end_time' => Carbon::create(2026, 2, 23, 18, 0, 0),
        ]);

        // 既存休憩（index=0）
        AttendanceBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::create(2026, 2, 23, 12, 0, 0),
            'end_time' => Carbon::create(2026, 2, 23, 12, 30, 0),
        ]);

        $response = $this->get(route('attendance.detail', $attendance));
        $response->assertStatus(200);

        // 休憩ラベル（1件あるので「休憩」と「休憩2（追加枠）」が出る）
        $response->assertSee('<th>休憩</th>', false);
        $response->assertSee('<th>休憩2</th>', false);

        $response->assertSee('value="12:00"', false);
        $response->assertSee('value="12:30"', false);
    }
}
