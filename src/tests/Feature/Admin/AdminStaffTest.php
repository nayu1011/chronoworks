<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ID14_01_管理者ユーザーが全一般ユーザーの氏名とメールアドレスを確認できる(): void
    {
        $this->loginAsAdmin();

        $staff1 = User::factory()->create([
            'role' => 'user',
            'name' => '山田 太郎',
            'email' => 'taro@example.com',
        ]);

        $staff2 = User::factory()->create([
            'role' => 'user',
            'name' => '佐藤 花子',
            'email' => 'hanako@example.com',
        ]);

        $response = $this->get(route('admin.staff.list'));
        $response->assertStatus(200);

        // 氏名・メール
        $response->assertSee($staff1->name);
        $response->assertSee($staff1->email);
        $response->assertSee($staff2->name);
        $response->assertSee($staff2->email);

        // 「月次勤怠 -> 詳細」リンク（スタッフ別勤怠一覧への導線）
        $response->assertSee(route('admin.attendance.staff', $staff1->id), false);
        $response->assertSee(route('admin.attendance.staff', $staff2->id), false);
    }

    /** @test */
    public function ID14_02_ユーザーの勤怠情報が正しく表示される(): void
    {
        $this->setNow(2026, 2, 23, 9, 0, 0);
        $this->loginAsAdmin();

        $staff = User::factory()->create([
            'role' => 'user',
            'name' => '山田 太郎',
        ]);

        // 表示月：2026-02 の中で1件だけ勤怠を作る
        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'date' => Carbon::create(2026, 2, 5)->toDateString(),
            'start_time' => Carbon::create(2026, 2, 5, 9, 0, 0),
            'end_time' => Carbon::create(2026, 2, 5, 18, 0, 0),
        ]);

        $response = $this->get(route('admin.attendance.staff', [
            'staff' => $staff->id,
            'month' => '2026-02',
        ]));

        $response->assertStatus(200);

        // 見出し
        $response->assertSee('山田 太郎さんの勤怠');

        // 表示年月（displayYm = Y/m）
        $response->assertSee('2026/02');

        // 日付表示は m/d (曜) 形式
        // 2026-02-05 は木曜
        $response->assertSee('02/05');
        $response->assertSee('(木)');

        // 出勤・退勤（H:i）
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        // 詳細リンク（勤怠詳細へ）
        $response->assertSee(
            route('admin.attendance.detail', ['attendance' => $attendance->id]),
            false
        );
    }

    /** @test */
    public function ID14_03_前月を押下した時に表示月の前月の情報が表示される(): void
    {
        $this->setNow(2026, 2, 23, 9, 0, 0);
        $this->loginAsAdmin();

        $staff = User::factory()->create(['role' => 'user']);

        // 前月（2026-01）に勤怠を作る
        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'date' => Carbon::create(2026, 1, 10)->toDateString(), // 01/10
            'start_time' => Carbon::create(2026, 1, 10, 9, 0, 0),
            'end_time' => Carbon::create(2026, 1, 10, 18, 0, 0),
        ]);

        // 前月表示にする（ボタン押下の結果と同じ状態）
        $response = $this->get(route('admin.attendance.staff', [
            'staff' => $staff->id,
            'month' => '2026-01',
        ]));

        $response->assertStatus(200);

        // 表示年月
        $response->assertSee('2026/01');

        // その月の勤怠が見える
        $response->assertSee('01/10');
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        // 詳細リンクも存在
        $response->assertSee(
            route('admin.attendance.detail', ['attendance' => $attendance->id]),
            false
        );

        // 「前月」リンク自体も正しい（month=prevMonth が付く）
        $response->assertSee('← 前月');
        $response->assertSee(
            route('admin.attendance.staff', ['staff' => $staff->id, 'month' => '2025-12']),
            false
        );
    }

    /** @test */
    public function ID14_04_翌月を押下した時に表示月の翌月の情報が表示される(): void
    {
        $this->setNow(2026, 2, 23, 9, 0, 0);
        $this->loginAsAdmin();

        $staff = User::factory()->create(['role' => 'user']);

        // 翌月（2026-03）に勤怠を作る
        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'date' => Carbon::create(2026, 3, 10)->toDateString(), // 03/10
            'start_time' => Carbon::create(2026, 3, 10, 9, 0, 0),
            'end_time' => Carbon::create(2026, 3, 10, 18, 0, 0),
        ]);

        // 翌月表示にする（ボタン押下の結果と同じ状態）
        $response = $this->get(route('admin.attendance.staff', [
            'staff' => $staff->id,
            'month' => '2026-03',
        ]));

        $response->assertStatus(200);

        // 表示年月
        $response->assertSee('2026/03');

        // その月の勤怠が見える
        $response->assertSee('03/10');
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        // 詳細リンクも存在
        $response->assertSee(
            route('admin.attendance.detail', ['attendance' => $attendance->id]),
            false
        );

        // 「翌月」リンク自体も正しい（month=nextMonth が付く）
        $response->assertSee('翌月 →');
        $response->assertSee(
            route('admin.attendance.staff', ['staff' => $staff->id, 'month' => '2026-04']),
            false
        );
    }

    /** @test */
    public function ID14_05_詳細を押下するとその日の勤怠詳細画面に遷移する(): void
    {
        $this->setNow(2026, 2, 23, 9, 0, 0);
        $this->loginAsAdmin();

        $staff = User::factory()->create(['role' => 'user']);

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'date' => Carbon::create(2026, 2, 10)->toDateString(),
            'start_time' => Carbon::create(2026, 2, 10, 9, 0, 0),
            'end_time' => Carbon::create(2026, 2, 10, 18, 0, 0),
        ]);

        // 一旦、スタッフ別勤怠一覧で「詳細」リンクが出ていること
        $listResponse = $this->get(route('admin.attendance.staff', [
            'staff' => $staff->id,
            'month' => '2026-02',
        ]));
        $listResponse->assertStatus(200);
        $listResponse->assertSee(
            route('admin.attendance.detail', ['attendance' => $attendance->id]),
            false
        );

        // 「詳細」押下相当：勤怠詳細ページへアクセスできること
        $detailResponse = $this->get(route('admin.attendance.detail', ['attendance' => $attendance->id]));
        $detailResponse->assertStatus(200);

        // 日付が出ていること
        $detailResponse->assertSeeInOrder([
            '<span class="date-year">2026年</span>',
            '<span class="date-md">2月10日</span>',
        ], false);
    }
}
