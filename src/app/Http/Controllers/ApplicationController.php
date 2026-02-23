<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationBreak;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AttendanceCorrectionStoreRequest;

class ApplicationController extends Controller
{
    // 申請のステータス定数
    private const ALLOWED_STATUSES = [Application::STATUS_PENDING, Application::STATUS_APPROVED];

    // PG06・PG12 申請一覧（一般）
    public function list(Request $request)
    {
        // ステータス（デフォルト：承認待ち）
        $status = $request->query('status', Application::STATUS_PENDING);

        // ステータスのバリデーション（念のため）
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            $status = Application::STATUS_PENDING;
        }

        // 申請一覧取得
        $query = Application::with([
                'applicant',
                'attendance',
            ])
            ->where('status', $status)
            ->orderByDesc('created_at');

        // 管理者は全員分の申請一覧を取得
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->can('admin')) {
            $applications = $query->get();

            return view('admin.application.list', [
                'applications' => $applications,
                'status'       => $status,
            ]);
        }

        // 一般ユーザーは自分の申請一覧のみ取得
        $applications = $query->where('applicant_user_id', $user->id)->get();

        return view('application.list', [
            'applications' => $applications,
            'status'       => $status,
        ]);

    }

    /**
     * 申請詳細
     */
    public function detail(Application $application)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 管理者は管理者用の承認画面へ誘導
        if ($user->can('admin')) {
            abort(403);
        }

        // 本人以外は閲覧不可
        if ($application->applicant_user_id !== $user->id) {
            abort(403);
        }

        return view('application.detail', compact('application'));
    }

    // 修正申請登録
    public function store(AttendanceCorrectionStoreRequest $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 管理者は申請登録できない（承認側のみ）
        if ($user->can('admin')) {
            abort(403);
        }

        $attendance = Attendance::findOrFail($request->attendance_id);

        // 念のため本人チェック
        if ($attendance->user_id !== $user->id) {
            abort(403);
        }

        $date = $attendance->date->format('Y-m-d');

        // 出勤・退勤（date + time を合成）
        $startTime = $request->start_time
            ? Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $request->start_time)
            : null;

        $endTime = $request->end_time
            ? Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $request->end_time)
            : null;

        $application = Application::create([
            'applicant_user_id' => $user->id,
            'attendance_id'     => $attendance->id,
            'start_time'        => $startTime,
            'end_time'          => $endTime,
            'comment'           => $request->comment,
            'status'            => Application::STATUS_PENDING,
        ]);

        // 休憩（片方入力は捨てる）
        foreach ($request->input('breaks', []) as $break) {
            $breakStart = $break['start_time'] ?? null;
            $breakEnd   = $break['end_time'] ?? null;

            // ✅ 片方でも空なら作らない（捨てる）
            if (empty($breakStart) || empty($breakEnd)) {
                continue;
            }

            ApplicationBreak::create([
                'application_id' => $application->id,
                'start_time'     => Carbon::createFromFormat('Y-m-d H:i', "{$date} {$breakStart}"),
                'end_time'       => Carbon::createFromFormat('Y-m-d H:i', "{$date} {$breakEnd}"),
            ]);
        }

        return redirect()->route('attendance.detail', $attendance);
    }
}
