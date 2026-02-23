<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ID12_01_その日になされた全ユーザーの勤怠情報が正確に確認できる(): void
    {
        $this->setNow(2026, 2, 23, 9, 0, 0);
        $date = Carbon::today()->toDateString();

        $this->loginAsAdmin();

        $userA = User::factory()->create(['name' => 'ユーザーA', 'role' => 'user']);
        $userB = User::factory()->create(['name' => 'ユーザーB', 'role' => 'user']);

        Attendance::factory()->create([
            'user_id' => $userA->id,
            'date' => $date,
            'start_time' => Carbon::parse("{$date} 09:00"),
            'end_time' => Carbon::parse("{$date} 18:00"),
        ]);

        Attendance::factory()->create([
            'user_id' => $userB->id,
            'date' => $date,
            'start_time' => Carbon::parse("{$date} 10:00"),
            'end_time' => Carbon::parse("{$date} 19:00"),
        ]);

        $response = $this->get(route('admin.attendance.list', ['date' => $date]));
        $response->assertStatus(200);

        // 日付表示
        $response->assertSee(Carbon::today()->format('Y/n/j'), false);

        // 全ユーザー分の行が出ていること
        $response->assertSee('ユーザーA', false);
        $response->assertSee('ユーザーB', false);

        // 詳細リンクがあること
        $attendanceA = Attendance::where('user_id', $userA->id)->whereDate('date', $date)->first();
        $attendanceB = Attendance::where('user_id', $userB->id)->whereDate('date', $date)->first();

        $this->assertNotNull($attendanceA);
        $this->assertNotNull($attendanceB);

        $response->assertSee(route('admin.attendance.detail', $attendanceA->id), false);
        $response->assertSee(route('admin.attendance.detail', $attendanceB->id), false);
    }

    /** @test */
    public function ID12_02_遷移した際に現在の日付が表示される(): void
    {
        $this->setNow(2026, 2, 23, 9, 0, 0);
        $date = Carbon::today()->toDateString();

        $this->loginAsAdmin();

        $response = $this->get(route('admin.attendance.list', ['date' => $date]));
        $response->assertStatus(200);

        // 日付表示
        $response->assertSee(Carbon::today()->format('Y/n/j'), false);
    }

    /** @test */
    public function ID12_03_前日を押下した時に前の日の勤怠情報が表示される(): void
    {
        $this->setNow(2026, 2, 23, 9, 0, 0);

        $today = Carbon::today();
        $prev = $today->copy()->subDay();

        $this->loginAsAdmin();

        $userPrev = User::factory()->create(['name' => '前日ユーザー', 'role' => 'user']);

        Attendance::factory()->create([
            'user_id' => $userPrev->id,
            'date' => $prev->toDateString(),
            'start_time' => Carbon::parse($prev->toDateString() . ' 09:00'),
            'end_time' => Carbon::parse($prev->toDateString() . ' 18:00'),
        ]);

        // 前日画面へ（「前日」リンク押下の代わり）
        $response = $this->get(route('admin.attendance.list', ['date' => $prev->toDateString()]));
        $response->assertStatus(200);

        $response->assertSee($prev->format('Y/n/j'), false);
        $response->assertSee('前日ユーザー', false);
        $response->assertSee('09:00', false);
        $response->assertSee('18:00', false);
    }

    /** @test */
    public function ID12_04_翌日を押下した時に次の日の勤怠情報が表示される(): void
    {
        $this->setNow(2026, 2, 23, 9, 0, 0);

        $today = Carbon::today();
        $next = $today->copy()->addDay();

        $this->loginAsAdmin();

        $userNext = User::factory()->create(['name' => '翌日ユーザー', 'role' => 'user']);

        Attendance::factory()->create([
            'user_id' => $userNext->id,
            'date' => $next->toDateString(),
            'start_time' => Carbon::parse($next->toDateString() . ' 10:00'),
            'end_time' => Carbon::parse($next->toDateString() . ' 19:00'),
        ]);

        // 翌日画面へ（「翌日」リンク押下の代わり）
        $response = $this->get(route('admin.attendance.list', ['date' => $next->toDateString()]));
        $response->assertStatus(200);

        $response->assertSee($next->format('Y/n/j'), false);
        $response->assertSee('翌日ユーザー', false);
        $response->assertSee('10:00', false);
        $response->assertSee('19:00', false);
    }
}
