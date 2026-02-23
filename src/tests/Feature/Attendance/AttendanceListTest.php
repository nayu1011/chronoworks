<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ID09_01_自分が行った勤怠情報が全て表示されている(): void
    {
        // 表示月を固定（例：2026-02）
        $this->setNow(2026, 2, 15, 9, 0, 0);

        $user = $this->loginAsUser();

        // 2月の勤怠を2日分作る（自分の分）
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::create(2026, 2, 3)->toDateString(),
            'start_time' => Carbon::create(2026, 2, 3, 9, 0, 0),
            'end_time' => Carbon::create(2026, 2, 3, 18, 0, 0),
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::create(2026, 2, 10)->toDateString(),
            'start_time' => Carbon::create(2026, 2, 10, 10, 0, 0),
            'end_time' => Carbon::create(2026, 2, 10, 19, 0, 0),
        ]);

        // 他人の勤怠（同月）を混ぜても表示されないことを担保
        $other = $this->loginAsUser(null, ['email' => 'other@example.com']); // いったん作成
        // 作った後にログインを戻す
        $this->actingAs($user);

        Attendance::factory()->create([
            'user_id' => $other->id,
            'date' => Carbon::create(2026, 2, 5)->toDateString(),
            'start_time' => Carbon::create(2026, 2, 5, 7, 7, 0),
            'end_time' => Carbon::create(2026, 2, 5, 17, 17, 0),
        ]);

        $response = $this->get(route('attendance.list', ['month' => '2026-02']));
        $response->assertStatus(200);

        // 自分の勤怠2件が「日付」と「出勤/退勤時刻」として表示されること
        $response->assertSee('02/03', false);
        $response->assertSee('09:00', false);
        $response->assertSee('18:00', false);

        $response->assertSee('02/10', false);
        $response->assertSee('10:00', false);
        $response->assertSee('19:00', false);

        // 他人の勤怠は表示されない（02/05 の行自体は日付として表示されるが、時刻は出ない）
        $response->assertDontSee('07:07', false);
        $response->assertDontSee('17:17', false);
    }

    /** @test */
    public function ID09_02_勤怠一覧画面に遷移した際に現在の月が表示される(): void
    {
        $this->setNow(2026, 2, 23, 1, 0, 0);

        $this->loginAsUser();

        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);

        $response->assertSee('2026/02', false);
    }

    /** @test */
    public function ID09_03_前月を押下した時に表示月の前月の情報が表示される(): void
    {
        $this->setNow(2026, 2, 23, 1, 0, 0);

        $this->loginAsUser();

        $response = $this->get(route('attendance.list', ['month' => '2026-02']));
        $response->assertStatus(200);

        // 前月リンクが存在する
        $response->assertSee(route('attendance.list', ['month' => '2026-01']), false);

        // 前月ページに行くと表示が 2026/01 になる
        $prev = $this->get(route('attendance.list', ['month' => '2026-01']));
        $prev->assertStatus(200);
        $prev->assertSee('2026/01', false);
    }

    /** @test */
    public function ID09_04_翌月を押下した時に表示月の翌月の情報が表示される(): void
    {
        $this->setNow(2026, 2, 23, 1, 0, 0);

        $this->loginAsUser();

        $response = $this->get(route('attendance.list', ['month' => '2026-02']));
        $response->assertStatus(200);

        // 翌月リンクが存在する
        $response->assertSee(route('attendance.list', ['month' => '2026-03']), false);

        // 翌月ページに行くと表示が 2026/03 になる
        $next = $this->get(route('attendance.list', ['month' => '2026-03']));
        $next->assertStatus(200);
        $next->assertSee('2026/03', false);
    }

    /** @test */
    public function ID09_05_詳細を押下するとその日の勤怠詳細画面に遷移する(): void
    {
        $this->setNow(2026, 2, 23, 1, 0, 0);

        $user = $this->loginAsUser();

        // 2/23 の勤怠を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'start_time' => Carbon::now()->subHours(2),
            'end_time' => Carbon::now()->subHour(),
        ]);

        $list = $this->get(route('attendance.list', ['month' => '2026-02']));
        $list->assertStatus(200);

        // 一覧に「詳細」リンクがあり、勤怠詳細URLが含まれていること
        $detailUrl = route('attendance.detail', $attendance);
        $list->assertSee($detailUrl, false);

        // 実際に詳細ページへアクセスできること
        $detail = $this->get($detailUrl);
        $detail->assertStatus(200);
    }
}
