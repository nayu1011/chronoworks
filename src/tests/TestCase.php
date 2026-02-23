<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * 一般ユーザーでログイン状態にする（role='user'）
     */
    protected function loginAsUser(?User $user = null, array $override = []): User
    {
        $user ??= User::factory()->create(array_merge([
            'role' => 'user',
        ], $override));

        $this->actingAs($user);

        return $user;
    }

    /**
     * 管理者ユーザーでログイン状態にする（role='admin'）
     */
    protected function loginAsAdmin(?User $admin = null, array $override = []): User
    {
        $admin ??= User::factory()->create(array_merge([
            'role' => 'admin',
        ], $override));

        $this->actingAs($admin);

        return $admin;
    }

    // 勤怠と休憩のテストデータを作成するメソッド
    protected function createAttendance(User $user, array $override = []): Attendance
    {
        return Attendance::factory()->create(array_merge([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'start_time' => null,
            'end_time' => null,
        ], $override));
    }

    protected function createBreak(Attendance $attendance, array $override = []): AttendanceBreak
    {
        return AttendanceBreak::factory()->create(array_merge([
            'attendance_id' => $attendance->id,
            'start_time' => null,
            'end_time' => null,
        ], $override));
    }

    /**
     * テスト用に現在時刻を固定する（画面表示/打刻時刻を安定させる）
     */
    protected function setNow(
        int $year = 2026,
        int $month = 2,
        int $day = 23,
        int $hour = 1,
        int $minute = 22,
        int $second = 0
    ): void {
        Carbon::setTestNow(Carbon::create($year, $month, $day, $hour, $minute, $second, 'Asia/Tokyo'));
        Carbon::setLocale('ja');
    }

    /**
     * setTestNow解除（他のテストへの影響を防ぐ）
     */
    protected function clearNow(): void
    {
        Carbon::setTestNow();
    }

    // 各テストの後にsetTestNowを解除
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
