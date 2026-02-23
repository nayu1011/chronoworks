<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\Application;
use App\Models\ApplicationBreak;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 日本人ユーザー10人
        $users = [
            '山田 太郎',
            '佐藤 花子',
            '鈴木 一郎',
            '高橋 美咲',
            '田中 健',
            '伊藤 直樹',
            '渡辺 彩',
            '小林 恒一',
            '加藤 由美',
            '中村 大輔',
        ];

        // 勤務期間
        $period = CarbonPeriod::create('2026-01-01', '2026-03-31');

        foreach ($users as $index => $name) {

            // ユーザー作成
            $user = User::create([
                'name' => $name,
                'email' => 'user' . ($index + 1) . '@example.com',
                'password' => Hash::make('password'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]);

            // 勤怠データ作成
            foreach ($period as $date) {

                $attendance = Attendance::create([
                    'user_id'    => $user->id,
                    'date'       => $date->toDateString(),
                    'start_time' => Carbon::parse($date)->setTime(9, 0),
                    'end_time'   => Carbon::parse($date)->setTime(18, 0),
                ]);

                // 休憩データ作成
                AttendanceBreak::create([
                    'attendance_id' => $attendance->id,
                    'start_time'    => Carbon::parse($date)->setTime(12, 0),
                    'end_time'      => Carbon::parse($date)->setTime(13, 0),
                ]);
            }
        }

        // 管理者作成
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // ==========================
        // 申請データ（最小セット）
        // pending 1件 / approved 1件
        // ==========================

        // 申請用の一般ユーザーを1人取得（このSeederで作った先頭ユーザー想定）
        $applicant = User::where('role', 'user')->orderBy('id')->first();

        if ($applicant) {
            // 勤怠を2日分拾う（期間内から適当に2件）
            $attendancePending = Attendance::where('user_id', $applicant->id)
                ->orderBy('date')
                ->first();

            $attendanceApproved = Attendance::where('user_id', $applicant->id)
                ->orderBy('date', 'desc')
                ->first();

            // 承認待ち申請
            if ($attendancePending) {
                $pending = Application::create([
                    'status' => 'pending',
                    'applicant_user_id' => $applicant->id,
                    'attendance_id' => $attendancePending->id,
                    'start_time' => Carbon::parse($attendancePending->date)->setTime(10, 0),
                    'end_time' => Carbon::parse($attendancePending->date)->setTime(19, 0),
                    'comment' => '電車遅延のため打刻修正をお願いします',
                    'approver_user_id' => null,
                    'approved_at' => null,
                ]);

                ApplicationBreak::create([
                    'application_id' => $pending->id,
                    'start_time' => Carbon::parse($attendancePending->date)->setTime(12, 30),
                    'end_time' => Carbon::parse($attendancePending->date)->setTime(13, 30),
                ]);
            }

            // 承認済み申請
            if ($attendanceApproved) {
                $approved = Application::create([
                    'status' => 'approved',
                    'applicant_user_id' => $applicant->id,
                    'attendance_id' => $attendanceApproved->id,
                    'start_time' => Carbon::parse($attendanceApproved->date)->setTime(12, 30),
                    'end_time' => Carbon::parse($attendanceApproved->date)->setTime(18, 30),
                    'comment' => '私用のため時刻修正をお願いします',
                    'approver_user_id' => $admin->id,
                    'approved_at' => now(),
                ]);

                ApplicationBreak::create([
                    'application_id' => $approved->id,
                    'start_time' => Carbon::parse($attendanceApproved->date)->setTime(12, 0),
                    'end_time' => Carbon::parse($attendanceApproved->date)->setTime(13, 0),
                ]);
            }
        }
    }
}
