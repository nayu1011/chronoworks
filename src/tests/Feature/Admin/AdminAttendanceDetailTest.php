<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ID13_01_勤怠詳細画面に表示されるデータが選択したものになっている(): void
    {
        $this->setNow(2026, 2, 23, 9, 0, 0);

        $this->loginAsAdmin();

        $user = User::factory()->create([
            'role' => 'user',
            'name' => '山田 太郎',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'start_time' => Carbon::create(2026, 2, 23, 9, 0, 0),
            'end_time' => Carbon::create(2026, 2, 23, 18, 0, 0),
            'comment' => 'テスト備考',
        ]);

        AttendanceBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::create(2026, 2, 23, 12, 0, 0),
            'end_time' => Carbon::create(2026, 2, 23, 12, 30, 0),
        ]);

        $response = $this->get(route('admin.attendance.detail', $attendance));
        $response->assertStatus(200);

        // 名前
        $response->assertSee('<span class="name">山田 太郎</span>', false);

        // 日付（span分割＋hidden）
        $response->assertSeeInOrder([
            '<span class="date-year">2026年</span>',
            '<span class="date-md">2月23日</span>',
        ], false);

        $response->assertSee(
            'name="date" value="' . Carbon::today()->toDateString() . '"',
            false
        );

        // 出勤・退勤
        $response->assertSee('name="start_time"', false);
        $response->assertSee('value="09:00"', false);
        $response->assertSee('name="end_time"', false);
        $response->assertSee('value="18:00"', false);

        // 休憩（既存1件＋追加枠）
        $response->assertSee('<th>休憩</th>', false);
        $response->assertSee('<th>休憩2</th>', false);
        $response->assertSee('name="breaks[0][start_time]"', false);
        $response->assertSee('name="breaks[0][end_time]"', false);
        $response->assertSee('value="12:00"', false);
        $response->assertSee('value="12:30"', false);

        // 備考
        $response->assertSee('name="comment"', false);
        $response->assertSee('テスト備考', false);
    }

    /** @test */
    public function ID13_02_出勤時間が退勤時間より後の場合_エラーメッセージが表示される(): void
    {
        $this->setNow(2026, 2, 23, 9, 0, 0);

        $this->loginAsAdmin();

        $user = User::factory()->create(['role' => 'user']);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'start_time' => Carbon::create(2026, 2, 23, 9, 0, 0),
            'end_time' => Carbon::create(2026, 2, 23, 18, 0, 0),
            'comment' => '既存備考',
        ]);

        $payload = [
            'date' => Carbon::today()->toDateString(),
            'start_time' => '19:00',
            'end_time' => '18:00',
            'comment' => '更新備考',
            'breaks' => [
                'new' => ['start_time' => null, 'end_time' => null],
            ],
        ];

        $response = $this
            ->from(route('admin.attendance.detail', $attendance))
            ->put(route('admin.attendance.update', $attendance), $payload);

        $response->assertRedirect(route('admin.attendance.detail', ['attendance' => $attendance->id]));

        // afterバリデーションで start_time にエラーを積んでいる想定
        $response->assertSessionHasErrors([
            'start_time' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);

        // 表示まで確認
        $this->get(route('admin.attendance.detail', ['attendance' => $attendance->id]))
            ->assertStatus(200)
            ->assertSee('出勤時間もしくは退勤時間が不適切な値です', false);
    }

    /** @test */
    public function ID13_03_休憩開始時間が退勤時間より後の場合_エラーメッセージが表示される(): void
    {
        $this->setNow(2026, 2, 23, 9, 0, 0);

        $this->loginAsAdmin();

        $user = User::factory()->create(['role' => 'user']);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'start_time' => Carbon::create(2026, 2, 23, 9, 0, 0),
            'end_time' => Carbon::create(2026, 2, 23, 18, 0, 0),
            'comment' => '既存備考',
        ]);

        AttendanceBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::create(2026, 2, 23, 12, 0, 0),
            'end_time' => Carbon::create(2026, 2, 23, 12, 30, 0),
        ]);

        $payload = [
            'date' => Carbon::today()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '18:00',
            'comment' => '更新備考',
            'breaks' => [
                ['start_time' => '18:10', 'end_time' => '18:20'], // 退勤より後（開始がアウト）
            ],
        ];

        $response = $this
            ->from(route('admin.attendance.detail', $attendance))
            ->put(route('admin.attendance.update', $attendance), $payload);

        $response->assertRedirect(route('admin.attendance.detail', $attendance));

        $response->assertSessionHasErrors([
            'breaks.0.start_time' => '休憩時間が不適切な値です',
        ]);

        // 表示確認
        $this->get(route('admin.attendance.detail', $attendance))
            ->assertStatus(200)
            ->assertSee('休憩時間が不適切な値です', false);
    }

    /** @test */
    public function ID13_04_休憩終了時間が退勤時間より後の場合_エラーメッセージが表示される(): void
    {
        $this->setNow(2026, 2, 23, 9, 0, 0);

        $this->loginAsAdmin();

        $user = User::factory()->create(['role' => 'user']);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'start_time' => Carbon::create(2026, 2, 23, 9, 0, 0),
            'end_time' => Carbon::create(2026, 2, 23, 18, 0, 0),
            'comment' => '既存備考',
        ]);

        AttendanceBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::create(2026, 2, 23, 12, 0, 0),
            'end_time' => Carbon::create(2026, 2, 23, 12, 30, 0),
        ]);

        $payload = [
            'date' => Carbon::today()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '18:00',
            'comment' => '更新備考',
            'breaks' => [
                ['start_time' => '17:50', 'end_time' => '18:10'], // 終了が退勤より後
            ],
        ];

        $response = $this
            ->from(route('admin.attendance.detail', $attendance))
            ->put(route('admin.attendance.update', $attendance), $payload);

        $response->assertRedirect(route('admin.attendance.detail', $attendance));

        $response->assertSessionHasErrors([
            'breaks.0.start_time' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);

        // 表示確認
        $this->get(route('admin.attendance.detail', $attendance))
            ->assertStatus(200)
            ->assertSee('休憩時間もしくは退勤時間が不適切な値です', false);
    }

    /** @test */
    public function ID13_05_備考欄が未入力の場合_エラーメッセージが表示される(): void
    {
        $this->setNow(2026, 2, 23, 9, 0, 0);

        $this->loginAsAdmin();

        $user = User::factory()->create(['role' => 'user']);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'start_time' => Carbon::create(2026, 2, 23, 9, 0, 0),
            'end_time' => Carbon::create(2026, 2, 23, 18, 0, 0),
            'comment' => '既存備考',
        ]);

        $payload = [
            'date' => Carbon::today()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '18:00',
            'comment' => '', // 未入力
            'breaks' => [
                'new' => ['start_time' => null, 'end_time' => null],
            ],
        ];

        $response = $this
            ->from(route('admin.attendance.detail', $attendance))
            ->put(route('admin.attendance.update', $attendance), $payload);

        $response->assertRedirect(route('admin.attendance.detail', $attendance));

        $response->assertSessionHasErrors([
            'comment' => '備考を記入してください',
        ]);

        // 表示確認までやる場合（任意）
        $this->get(route('admin.attendance.detail', $attendance))
            ->assertStatus(200)
            ->assertSee('備考を記入してください', false);
    }
}
