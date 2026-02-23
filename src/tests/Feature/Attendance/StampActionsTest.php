<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StampActionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ID06_01_出勤ボタンが正しく機能する(): void
    {
        $this->setNow();

        $user = $this->loginAsUser();

        $this->post(route('attendance.start'))->assertStatus(302);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
        ]);
    }

    /** @test */
    public function ID06_02_出勤は1日1回のみできる(): void
    {
        $this->setNow();

        $user = $this->loginAsUser();

        // 1回目
        $this->post(route('attendance.start'))->assertStatus(302);

        // 2回目（同日）
        $this->post(route('attendance.start'))->assertStatus(302);

        $attendances = Attendance::where('user_id', $user->id)
            ->whereDate('date', Carbon::today())
            ->get();

        $this->assertCount(1, $attendances);
    }

    /** @test */
    public function ID06_03_出勤時刻が勤怠一覧画面で確認できる(): void
    {
        $this->setNow();

        $this->loginAsUser();

        $this->post(route('attendance.start'))->assertStatus(302);

        $startTime = Carbon::now()->format('H:i');

        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);

        $jpWeek = ['日','月','火','水','木','金','土'];

        $response->assertSeeInOrder([
            Carbon::today()->format('m/d'),
            '(' . $jpWeek[Carbon::today()->dayOfWeek] . ')',
            $startTime,
        ], false);
    }

    /** @test */
    public function ID07_01_休憩入ボタンが正しく機能しステータスが休憩中になる(): void
    {
        $this->setNow();

        $user = $this->loginAsUser();

        // 出勤中状態を作る（出勤済み・退勤なし）
        $attendance = $this->createAttendance($user, [
            'start_time' => Carbon::now()->subHour(),
            'end_time' => null,
        ]);

        // 画面に「休憩入」ボタンがある（出勤中UI）
        $index = $this->get(route('attendance.index'));
        $index->assertStatus(200);
        $index->assertSee('出勤中', false);
        $index->assertSee('休憩入', false);

        // 休憩入
        $this->post(route('break.start'))->assertStatus(302);

        // DBに休憩が作られている
        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
        ]);

        // 画面ステータスが「休憩中」になり、「休憩戻」が表示される
        $indexAfter = $this->get(route('attendance.index'));
        $indexAfter->assertStatus(200);
        $indexAfter->assertSee('休憩中', false);
        $indexAfter->assertSee('休憩戻', false);
    }

    /** @test */
    public function ID07_03_休憩戻ボタンが正しく機能しステータスが出勤中に戻る(): void
    {
        $this->setNow();

        $user = $this->loginAsUser();

        $attendance = $this->createAttendance($user, [
            'start_time' => Carbon::now()->subHour(),
            'end_time' => null,
        ]);

        // 休憩中状態を作る（end_timeがnullの休憩）
        AttendanceBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::now()->subMinutes(10),
            'end_time' => null,
        ]);

        // 画面に「休憩戻」ボタンがある（休憩中UI）
        $index = $this->get(route('attendance.index'));
        $index->assertStatus(200);
        $index->assertSee('休憩中', false);
        $index->assertSee('休憩戻', false);

        // 休憩戻
        $this->post(route('break.end'))->assertStatus(302);

        // 最新休憩の end_time が埋まっている
        $latestBreak = AttendanceBreak::where('attendance_id', $attendance->id)
            ->latest('start_time')
            ->first();

        $this->assertNotNull($latestBreak);
        $this->assertNotNull($latestBreak->end_time);

        // 画面ステータスが「出勤中」に戻り、「休憩入」「退勤」が表示される
        $indexAfter = $this->get(route('attendance.index'));
        $indexAfter->assertStatus(200);
        $indexAfter->assertSee('出勤中', false);
        $indexAfter->assertSee('休憩入', false);
        $indexAfter->assertSee('退勤', false);
    }

    /** @test */
    public function ID07_02_休憩は一日に何回でもできる(): void
    {
        $this->setNow();

        $user = $this->loginAsUser();

        $this->createAttendance($user, [
            'start_time' => Carbon::now()->subHour(),
            'end_time' => null,
        ]);

        // 休憩入 → 休憩戻
        $this->post(route('break.start'))->assertStatus(302);
        $this->post(route('break.end'))->assertStatus(302);

        // 再び「休憩入」ボタンが表示される（＝再度休憩できる）
        $index = $this->get(route('attendance.index'));
        $index->assertStatus(200);
        $index->assertSee('出勤中', false);
        $index->assertSee('休憩入', false);
    }

    /** @test */
    public function ID07_04_休憩戻は一日に何回でもできる(): void
    {
        $this->setNow();

        $user = $this->loginAsUser();

        $attendance = $this->createAttendance($user, [
            'start_time' => Carbon::now()->subHour(),
            'end_time' => null,
        ]);

        // 1回目：休憩入 → 休憩戻
        $this->post(route('break.start'))->assertStatus(302);
        $this->post(route('break.end'))->assertStatus(302);

        // 2回目：休憩入 → 休憩戻
        $this->post(route('break.start'))->assertStatus(302);
        $this->post(route('break.end'))->assertStatus(302);

        // 休憩レコードが2件あり、両方 end_time が入っている
        $breaks = AttendanceBreak::where('attendance_id', $attendance->id)
            ->orderBy('start_time')
            ->get();

        $this->assertCount(2, $breaks);
        $this->assertNotNull($breaks[0]->end_time);
        $this->assertNotNull($breaks[1]->end_time);
    }

    /** @test */
    public function ID07_05_休憩時刻が勤怠一覧画面で確認できる(): void
    {
        // 時刻を固定して「休憩合計 0:05」を作る
        $this->setNow(2026, 2, 23, 1, 0, 0);

        $user = $this->loginAsUser();

        $this->createAttendance($user, [
            'start_time' => Carbon::now(),
            'end_time' => null,
        ]);

        // 休憩入：01:10
        Carbon::setTestNow(Carbon::now()->addMinutes(10));
        $this->post(route('break.start'))->assertStatus(302);

        // 休憩戻：01:15（5分休憩）
        Carbon::setTestNow(Carbon::now()->addMinutes(5));
        $this->post(route('break.end'))->assertStatus(302);

        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);

        $jpWeek = ['日','月','火','水','木','金','土'];

        // 5分休憩 → "0:05"
        $response->assertSeeInOrder([
        Carbon::today()->format('m/d'),
        '(' . $jpWeek[Carbon::today()->dayOfWeek] . ')',
        '0:05',
        ], false);
    }

    /** @test */
    public function ID08_01_退勤ボタンが正しく機能しステータスが退勤済になる(): void
    {
        $this->setNow();

        $user = $this->loginAsUser();

        $this->createAttendance($user, [
            'start_time' => Carbon::now()->subHour(),
            'end_time' => null,
        ]);

        // 画面に「退勤」ボタンがある（出勤中UI）
        $index = $this->get(route('attendance.index'));
        $index->assertStatus(200);
        $index->assertSee('出勤中', false);
        $index->assertSee('退勤', false);

        // 退勤
        $this->post(route('attendance.end'))->assertStatus(302);

        // 画面ステータスが「退勤済」になり、メッセージ表示
        $indexAfter = $this->get(route('attendance.index'));
        $indexAfter->assertStatus(200);
        $indexAfter->assertSee('退勤済', false);
        $indexAfter->assertSee('お疲れ様でした。', false);
    }

    /** @test */
    public function ID08_02_退勤時刻が勤怠一覧画面で確認できる(): void
    {
        // 出勤：01:00
        $this->setNow(2026, 2, 23, 1, 0, 0);

        $user = $this->loginAsUser();

        $this->post(route('attendance.start'))->assertStatus(302);

        $startTime = Carbon::now()->format('H:i');

        // 退勤：02:00
        Carbon::setTestNow(Carbon::now()->addHour());
        $this->post(route('attendance.end'))->assertStatus(302);

        $endTime = Carbon::now()->format('H:i');

        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);

        $jpWeek = ['日','月','火','水','木','金','土'];

        $response->assertSeeInOrder([
            Carbon::today()->format('m/d'),
            '(' . $jpWeek[Carbon::today()->dayOfWeek] . ')',
            $startTime,
            $endTime
        ], false);
    }
}
