<?php

namespace Tests\Feature\Attendance;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceIndexTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ID04_01_現在の日時が画面表示と一致する(): void
    {
        // 画面の表示に合わせて時刻固定（スクショの形式）
        Carbon::setTestNow(Carbon::create(2026, 2, 23, 1, 22, 0, 'Asia/Tokyo'));
        Carbon::setLocale('ja');

        $this->loginAsUser();

        $response = $this->get(route('attendance.index'));

        $response->assertStatus(200);

        // 日付表示：2026年2月23日(月)
        $response->assertSee('2026年2月23日(月)', false);

        // 時刻表示：01:22
        $response->assertSee('01:22', false);
    }
}
