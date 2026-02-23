<?php

namespace Tests\Feature\Application;

use App\Models\Application;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionFlowTest extends TestCase
{
    use RefreshDatabase;

    private const FIX_Y = 2026;
    private const FIX_M = 2;
    private const FIX_D = 23;

    /**
     * 勤怠（+休憩1件）を作って、修正申請Payloadのひな形を返す
     *
     * @return array{0:\App\Models\Attendance,1:array<string,mixed>}
     */
    private function seedAttendanceAndPayload(int $userId, array $overridePayload = []): array
    {
        // テスト日付を固定
        $this->setNow(self::FIX_Y, self::FIX_M, self::FIX_D, 10, 0, 0);

        $attendance = Attendance::factory()->create([
            'user_id' => $userId,
            'date' => Carbon::today()->toDateString(),
            'start_time' => Carbon::parse(Carbon::today()->toDateString() . ' 09:00'),
            'end_time' => Carbon::parse(Carbon::today()->toDateString() . ' 18:00'),
        ]);

        AttendanceBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::parse(Carbon::today()->toDateString() . ' 12:00'),
            'end_time' => Carbon::parse(Carbon::today()->toDateString() . ' 13:00'),
        ]);

        // AttendanceCorrectionStoreRequest の rules に合わせる
        $payload = [
            'attendance_id' => $attendance->id,
            'date' => $attendance->date->toDateString(),
            'start_time' => '09:00',
            'end_time' => '18:00',
            'breaks' => [
                [
                    'start_time' => '12:00',
                    'end_time' => '13:00',
                ],
            ],
            'comment' => '修正申請テスト',
        ];

        $payload = array_replace_recursive($payload, $overridePayload);

        return [$attendance, $payload];
    }

    /** @test */
    public function ID11_01_出勤時間が退勤時間より後の場合_エラーメッセージが表示される(): void
    {
        $user = $this->loginAsUser();

        [$attendance, $payload] = $this->seedAttendanceAndPayload($user->id, [
            'start_time' => '19:00',
            'end_time' => '18:00',
        ]);

        $this->from(route('attendance.detail', $attendance))
            ->post(route('application.store'), $payload)
            ->assertStatus(302)
            ->assertRedirect(route('attendance.detail', $attendance))
            ->assertSessionHasErrors([
                'start_time' => '出勤時間もしくは退勤時間が不適切な値です',
            ]);
    }

    /** @test */
    public function ID11_02_休憩開始が退勤より後の場合_エラーメッセージが表示される(): void
    {
        $user = $this->loginAsUser();

        [$attendance, $payload] = $this->seedAttendanceAndPayload($user->id, [
            'end_time' => '18:00',
            'breaks' => [
                [
                    'start_time' => '18:30', // 退勤より後
                    'end_time' => '18:40',
                ],
            ],
        ]);

        $this->from(route('attendance.detail', $attendance))
            ->post(route('application.store'), $payload)
            ->assertStatus(302)
            ->assertRedirect(route('attendance.detail', $attendance))
            ->assertSessionHasErrors([
                'breaks.0.start_time' => '休憩時間が不適切な値です',
            ]);
    }

    /** @test */
    public function ID11_03_休憩終了が退勤より後の場合_エラーメッセージが表示される(): void
    {
        $user = $this->loginAsUser();

        [$attendance, $payload] = $this->seedAttendanceAndPayload($user->id, [
            'end_time' => '18:00',
            'breaks' => [
                [
                    'start_time' => '17:50',
                    'end_time' => '18:10', // 退勤より後
                ],
            ],
        ]);

        $this->from(route('attendance.detail', $attendance))
            ->post(route('application.store'), $payload)
            ->assertStatus(302)
            ->assertRedirect(route('attendance.detail', $attendance))
            ->assertSessionHasErrors([
                'breaks.0.start_time' => '休憩時間もしくは退勤時間が不適切な値です',
            ]);
    }

    /** @test */
    public function ID11_04_備考未入力の場合_エラーメッセージが表示される(): void
    {
        $user = $this->loginAsUser();

        [$attendance, $payload] = $this->seedAttendanceAndPayload($user->id, [
            'comment' => '',
        ]);

        $this->from(route('attendance.detail', $attendance))
            ->post(route('application.store'), $payload)
            ->assertStatus(302)
            ->assertRedirect(route('attendance.detail', $attendance))
            ->assertSessionHasErrors([
                'comment' => '備考を記入してください',
            ]);
    }

    /** @test */
    public function ID11_05_修正申請処理が実行され_管理者の申請一覧画面と承認画面に表示される(): void
    {
        // 一般ユーザーで申請作成
        $user = $this->loginAsUser(null, ['name' => 'テスト太郎']);

        [$attendance, $payload] = $this->seedAttendanceAndPayload($user->id, [
            'start_time' => '10:00',
            'end_time' => '19:00',
            'breaks' => [
                [
                    'start_time' => '12:30',
                    'end_time' => '13:00',
                ],
            ],
            'comment' => '申請理由A',
        ]);

        $this->from(route('attendance.detail', $attendance))
            ->post(route('application.store'), $payload)
            ->assertStatus(302)
            ->assertRedirect(route('attendance.detail', $attendance));

        // DB：申請が pending で作成されている
        $application = Application::where('attendance_id', $attendance->id)
            ->where('applicant_user_id', $user->id)
            ->first();

        $this->assertNotNull($application);
        $this->assertSame('pending', $application->status);
        $this->assertSame('申請理由A', $application->comment);

        // 一般：申請一覧（承認待ち）に出る
        $list = $this->get(route('application.list', ['status' => 'pending']));
        $list->assertStatus(200);

        $list->assertSee('承認待ち', false);
        $list->assertSee('テスト太郎', false);
        $list->assertSee(Carbon::today()->format('Y/m/d'), false);
        $list->assertSee('申請理由A', false);

        // 「詳細」リンクは attendance.detail に飛ぶ仕様
        $list->assertSee(route('attendance.detail', $attendance), false);

        // 管理者：承認画面（詳細）に出ることを確認
        $this->loginAsAdmin(null, ['name' => '管理者太郎']);

        $adminDetail = $this->get(route('admin.application.detail', $application));
        $adminDetail->assertStatus(200);

        $adminDetail->assertSee('テスト太郎', false);
        $adminDetail->assertSee('申請理由A', false);
    }

    /** @test */
    public function ID11_06_承認待ちにログインユーザーの申請が全て表示される(): void
    {
        $user = $this->loginAsUser(null, ['name' => 'テスト太郎']);

        // 申請を2件作る
        [$attendance1, $payload1] = $this->seedAttendanceAndPayload($user->id, ['comment' => '申請理由A']);
        $this->post(route('application.store'), $payload1)->assertStatus(302);

        // 翌日勤怠で2件目
        $this->setNow(self::FIX_Y, self::FIX_M, self::FIX_D + 1, 10, 0, 0);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'start_time' => Carbon::parse(Carbon::today()->toDateString() . ' 09:00'),
            'end_time' => Carbon::parse(Carbon::today()->toDateString() . ' 18:00'),
        ]);

        $payload2 = [
            'attendance_id' => $attendance2->id,
            'date' => $attendance2->date->toDateString(),
            'start_time' => '09:00',
            'end_time' => '18:00',
            'breaks' => [],
            'comment' => '申請理由B',
        ];

        $this->post(route('application.store'), $payload2)->assertStatus(302);

        // 承認待ち一覧
        $list = $this->get(route('application.list', ['status' => 'pending']));
        $list->assertStatus(200);

        $list->assertSee('承認待ち', false);
        $list->assertSee('申請理由A', false);
        $list->assertSee('申請理由B', false);

        // 2件とも「詳細（=勤怠詳細）」リンクが存在する
        $list->assertSee(route('attendance.detail', $attendance1), false);
        $list->assertSee(route('attendance.detail', $attendance2), false);
    }

    /** @test */
    public function ID11_07_承認済みに管理者が承認した申請が全て表示されている(): void
    {
        // 一般ユーザーで申請作成
        $user = $this->loginAsUser(null, ['name' => 'テスト太郎']);

        [$attendance, $payload] = $this->seedAttendanceAndPayload($user->id, ['comment' => '申請理由A']);
        $this->post(route('application.store'), $payload)->assertStatus(302);

        $application = Application::where('attendance_id', $attendance->id)->first();
        $this->assertNotNull($application);

        // 管理者で承認
        $this->loginAsAdmin();

        $this->post(route('admin.application.approve', $application))
            ->assertStatus(302)
            ->assertRedirect(route('admin.application.detail', $application));

        $application->refresh();
        $this->assertSame('approved', $application->status);
        $this->assertNotNull($application->approved_at);

        // 一般ユーザー：承認済み一覧に出る
        $this->loginAsUser($user);

        $approvedList = $this->get(route('application.list', ['status' => 'approved']));
        $approvedList->assertStatus(200);

        $approvedList->assertSee('承認済み', false);
        $approvedList->assertSee('申請理由A', false);
        $approvedList->assertSee(route('attendance.detail', $attendance), false);
    }

    /** @test */
    public function ID11_08_各申請の詳細を押下すると勤怠詳細画面に遷移する(): void
    {
        $user = $this->loginAsUser(null, ['name' => 'テスト太郎']);

        [$attendance, $payload] = $this->seedAttendanceAndPayload($user->id, ['comment' => '申請理由A']);
        $this->post(route('application.store'), $payload)->assertStatus(302);

        // 申請一覧の「詳細」リンクは attendance.detail を指す
        $list = $this->get(route('application.list', ['status' => 'pending']));
        $list->assertStatus(200);

        $detailUrl = route('attendance.detail', $attendance);
        $list->assertSee($detailUrl, false);

        // 実際に遷移できること
        $detail = $this->get($detailUrl);
        $detail->assertStatus(200);
        $detail->assertSee('勤怠詳細', false);
        $detail->assertSee('テスト太郎', false);
    }
}
