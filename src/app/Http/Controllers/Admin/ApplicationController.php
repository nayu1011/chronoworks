<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ApplicationController extends Controller
{
    // PG13（承認画面）
    public function detail(Application $application)
    {
        $application->load(['attendance.attendanceBreaks', 'applicationBreaks', 'applicant']);
        return view('admin.application.detail', compact('application'));
    }

    // PG13（承認処理）
    public function approve(Request $request, Application $application)
    {
        // 二重承認防止
        if ($application->status !== Application::STATUS_PENDING) {
            return redirect()
                ->route('admin.application.detail', $application);
        }

        DB::transaction(function () use ($application) {

            $attendance = $application->attendance;

            // 勤怠本体を更新
            $attendance->update([
                'start_time' => $application->start_time,
                'end_time'   => $application->end_time,
            ]);

            // 既存の休憩を削除 → 申請の休憩で作り直す
            $attendance->attendanceBreaks()->delete();

            foreach ($application->applicationBreaks as $break) {
                $attendance->attendanceBreaks()->create([
                    'start_time' => $break->start_time,
                    'end_time'   => $break->end_time,
                ]);
            }

            // 申請を承認済みにする
            $application->update([
                'status'      => Application::STATUS_APPROVED,
                'approved_at' => now(),
                'approver_user_id' => Auth::id(),
            ]);
        });

        return redirect()
            ->route('admin.application.detail', $application);
    }
}
