<?php

namespace Tests\Feature\Admin;

use App\Models\Application;
use App\Models\ApplicationBreak;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApplicationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ID15_01_承認待ちの修正申請が全て表示されている(): void
    {
        $this->setNow(2026, 2, 23, 9, 0, 0);
        $this->loginAsAdmin();

        $user1 = User::factory()->create(['role' => 'user', 'name' => '山田 太郎']);
        $user2 = User::factory()->create(['role' => 'user', 'name' => '佐藤 花子']);

                $attendance1 = Attendance::factory()->create([
                    'user_id' => $user1->id,
                    'date' => Carbon::create(2026, 2, 10)->toDateString(),
                    'start_time' => Carbon::create(2026, 2, 10, 9, 0, 0),
                    'end_time' => Carbon::create(2026, 2, 10, 18, 0, 0),
                ]);

                $attendance2 = Attendance::factory()->create([
                    'user_id' => $user2->id,
                    'date' => Carbon::create(2026, 2, 11)->toDateString(),
                    'start_time' => Carbon::create(2026, 2, 11, 9, 0, 0),
                    'end_time' => Carbon::create(2026, 2, 11, 18, 0, 0),
                ]);

        $app1 = Application::create([
            'status' => 'pending',
            'applicant_user_id' => $user1->id,
            'attendance_id' => $attendance1->id,
            'start_time' => Carbon::create(2026, 2, 10, 9, 0, 0),
            'end_time' => Carbon::create(2026, 2, 10, 18, 0, 0),
            'comment' => '申請理由A',
        ]);

        $app2 = Application::create([
            'status' => 'pending',
            'applicant_user_id' => $user2->id,
            'attendance_id' => $attendance2->id,
            'start_time' => Carbon::create(2026, 2, 11, 10, 0, 0),
            'end_time' => Carbon::create(2026, 2, 11, 19, 0, 0),
            'comment' => '申請理由B',
        ]);

        // 承認済みも混ぜる（pending一覧に出ないことを担保）
        $approved = Application::create([
            'status' => 'approved',
            'applicant_user_id' => $user1->id,
            'attendance_id' => $attendance1->id,
            'start_time' => Carbon::create(2026, 2, 10, 8, 30, 0),
            'end_time' => Carbon::create(2026, 2, 10, 17, 30, 0),
            'comment' => '承認済み理由',
            'approved_at' => now(),
        ]);

        $response = $this->get(route('application.list', ['status' => 'pending']));
        $response->assertStatus(200);

        // pending 2件が表示
        $response->assertSee('承認待ち');
        $response->assertSee($user1->name);
        $response->assertSee($user2->name);

        // 対象日時（attendance date: Y/m/d）
        $response->assertSee('2026/02/10');
        $response->assertSee('2026/02/11');

        // 申請理由
        $response->assertSee('申請理由A');
        $response->assertSee('申請理由B');

        // 詳細リンク（admin.detail に飛ぶ）
        $response->assertSee(route('admin.application.detail', $app1->id), false);
        $response->assertSee(route('admin.application.detail', $app2->id), false);

        // approvedは出ない（名前や理由が混ざらない）
        $response->assertDontSee('承認済み理由');
        $response->assertDontSee(route('admin.application.detail', $approved->id), false);
    }

    /** @test */
    public function ID15_02_承認済みの修正申請が全て表示されている(): void
    {
        $this->setNow(2026, 2, 23, 9, 0, 0);
        $this->loginAsAdmin();

        $user = User::factory()->create(['role' => 'user', 'name' => '山田 太郎']);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::create(2026, 2, 12)->toDateString(),
            'start_time' => Carbon::create(2026, 2, 12, 9, 0, 0),
            'end_time' => Carbon::create(2026, 2, 12, 18, 0, 0),
        ]);

        $approved1 = Application::create([
            'status' => 'approved',
            'applicant_user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::create(2026, 2, 12, 9, 0, 0),
            'end_time' => Carbon::create(2026, 2, 12, 18, 0, 0),
            'comment' => '承認済み理由A',
            'approved_at' => now(),
        ]);

        $pending = Application::create([
            'status' => 'pending',
            'applicant_user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::create(2026, 2, 12, 10, 0, 0),
            'end_time' => Carbon::create(2026, 2, 12, 19, 0, 0),
            'comment' => '未承認理由',
        ]);

        $response = $this->get(route('application.list', ['status' => 'approved']));
        $response->assertStatus(200);

        $response->assertSee('承認済み');
        $response->assertSee($user->name);
        $response->assertSee('2026/02/12');
        $response->assertSee('承認済み理由A');
        $response->assertSee(route('admin.application.detail', $approved1->id), false);

        // pendingは出ない
        $response->assertDontSee('未承認理由');
        $response->assertDontSee(route('admin.application.detail', $pending->id), false);
    }

    /** @test */
    public function ID15_03_修正申請の詳細内容が正しく表示されている(): void
    {
        $this->setNow(2026, 2, 23, 9, 0, 0);
        $this->loginAsAdmin();

        $user = User::factory()->create(['role' => 'user', 'name' => '山田 太郎']);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::create(2026, 2, 15)->toDateString(),
            'start_time' => Carbon::create(2026, 2, 15, 9, 0, 0),
            'end_time' => Carbon::create(2026, 2, 15, 18, 0, 0),
        ]);

        $application = Application::create([
            'status' => 'pending',
            'applicant_user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::create(2026, 2, 15, 9, 30, 0),
            'end_time' => Carbon::create(2026, 2, 15, 18, 30, 0),
            'comment' => '申請理由テスト',
        ]);

        // 休憩2件
        ApplicationBreak::create([
            'application_id' => $application->id,
            'start_time' => Carbon::create(2026, 2, 15, 12, 0, 0),
            'end_time' => Carbon::create(2026, 2, 15, 12, 30, 0),
        ]);
        ApplicationBreak::create([
            'application_id' => $application->id,
            'start_time' => Carbon::create(2026, 2, 15, 15, 0, 0),
            'end_time' => Carbon::create(2026, 2, 15, 15, 10, 0),
        ]);

        $response = $this->get(route('admin.application.detail', $application));
        $response->assertStatus(200);

        // 名前
        $response->assertSee('<span class="name">山田 太郎</span>', false);

        // 日付（span分割）
        $response->assertSeeInOrder([
            '<span class="date-year">2026年</span>',
            '<span class="date-md">2月15日</span>',
        ], false);

        // 出勤・退勤（readonly input value）
        $response->assertSee('name="start_time"', false);
        $response->assertSee('value="09:30"', false);
        $response->assertSee('name="end_time"', false);
        $response->assertSee('value="18:30"', false);

        // 休憩ラベルと値
        $response->assertSee('<th>休憩</th>', false);
        $response->assertSee('<th>休憩 2</th>', false);
        $response->assertSee('value="12:00"', false);
        $response->assertSee('value="12:30"', false);
        $response->assertSee('value="15:00"', false);
        $response->assertSee('value="15:10"', false);

        // 備考
        $response->assertSee('name="comment"', false);
        $response->assertSee('申請理由テスト', false);

        // pendingなら承認ボタンがある
        $response->assertSee('承認する');
        $response->assertSee(route('admin.application.approve', $application), false);
    }

    /** @test */
    public function ID15_04_修正申請の承認処理が正しく行われる(): void
    {
        $this->setNow(2026, 2, 23, 9, 0, 0);
        $this->loginAsAdmin();

        $user = User::factory()->create(['role' => 'user']);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::create(2026, 2, 20)->toDateString(),
            'start_time' => Carbon::create(2026, 2, 20, 9, 0, 0),
            'end_time' => Carbon::create(2026, 2, 20, 18, 0, 0),
        ]);

        // 既存休憩（承認後に置換される想定）
        AttendanceBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::create(2026, 2, 20, 12, 0, 0),
            'end_time' => Carbon::create(2026, 2, 20, 12, 30, 0),
        ]);

        $application = Application::create([
            'status' => 'pending',
            'applicant_user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::create(2026, 2, 20, 10, 0, 0),
            'end_time' => Carbon::create(2026, 2, 20, 19, 0, 0),
            'comment' => '承認テスト',
        ]);

        // 申請休憩（これが勤怠休憩として作り直される）
        ApplicationBreak::create([
            'application_id' => $application->id,
            'start_time' => Carbon::create(2026, 2, 20, 13, 0, 0),
            'end_time' => Carbon::create(2026, 2, 20, 13, 15, 0),
        ]);
        ApplicationBreak::create([
            'application_id' => $application->id,
            'start_time' => Carbon::create(2026, 2, 20, 16, 0, 0),
            'end_time' => Carbon::create(2026, 2, 20, 16, 10, 0),
        ]);

        $response = $this->post(route('admin.application.approve', $application));
        $response->assertRedirect(route('admin.application.detail', $application));

        // Application が approved になる
        $application->refresh();
        $this->assertSame('approved', $application->status);
        $this->assertNotNull($application->approved_at);

        // Attendance が申請値に更新される
        $attendance->refresh();
        $this->assertSame('10:00', $attendance->start_time->format('H:i'));
        $this->assertSame('19:00', $attendance->end_time->format('H:i'));

        // 休憩が申請の内容に置換される（2件になっている）
        $this->assertSame(2, $attendance->attendanceBreaks()->count());

        $breakTimes = $attendance->attendanceBreaks()
            ->orderBy('start_time')
            ->get()
            ->map(fn ($b) => $b->start_time->format('H:i') . '-' . $b->end_time->format('H:i'))
            ->all();

        $this->assertSame(['13:00-13:15', '16:00-16:10'], $breakTimes);
    }
}
