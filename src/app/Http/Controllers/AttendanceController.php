<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Application;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    // PG03 勤怠登録画面（今日）
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now();

        $attendance = $this->findTodayAttendance($user->id);

        $status = 'before_work';

        if ($attendance) {
            // 状態判定は「退勤後 → 休憩中 → 出勤中」の順で行う
            if ($attendance->end_time) {
                // 退勤後
                $status = 'after_work';
            } elseif ($attendance->attendanceBreaks()->whereNull('end_time')->exists()) {
                // 休憩中
                $status = 'on_break';
            } else {
                // 出勤中
                $status = 'working';
            }
        }

        return view('attendance.index', compact(
            'today',
            'attendance',
            'status',
            'now'
        ));
    }

    // 出勤
    public function start()
    {
        $user = Auth::user();
        $attendance = $this->findTodayAttendance($user->id);

        // すでに本日の勤怠が存在する場合は二重出勤防止
        if ($attendance) {
            return redirect()
                ->route('attendance.index');
        }

        Attendance::create([
            'user_id'   => $user->id,
            'date' => today()->toDateString(),
            'start_time'  => now(),
        ]);

        return redirect()->route('attendance.index');
    }

    // 退勤
    public function end()
    {
        $user = Auth::user();
        $attendance = $this->findTodayAttendance($user->id);

        // 勤怠が存在しない場合
        if (! $attendance) {
            return redirect()
                ->route('attendance.index');
        }

        // すでに退勤済みの場合
        if ($attendance->end_time) {
            return redirect()
                ->route('attendance.index');
        }

        // 休憩中の場合は退勤不可
        if ($attendance->attendanceBreaks()->whereNull('end_time')->exists()) {
            return redirect()
                ->route('attendance.index');
        }

        $attendance->update([
            'end_time' => now(),
        ]);

        return redirect()->route('attendance.index');
    }

    // PG04 勤怠一覧
    public function list(Request $request)
    {
        $user = Auth::user();

        $currentYm = $request->input('month', now()->format('Y-m'));

        $current = Carbon::createFromFormat('Y-m', $currentYm);

        $prevMonth = $current->copy()->subMonth()->format('Y-m');
        $nextMonth = $current->copy()->addMonth()->format('Y-m');
        $displayYm = $current->format('Y/m');

        $startOfMonth = $current->copy()->startOfMonth();
        $endOfMonth   = $current->copy()->endOfMonth();

        $attendances = Attendance::with('attendanceBreaks')
            ->where('user_id', $user->id)
            ->whereBetween('date', [
                $startOfMonth->toDateString(),
                $endOfMonth->toDateString()
            ])
            ->get()
            ->keyBy(fn ($attendance) => $attendance->date->toDateString());

        $dates = collect();
        for ($date = $startOfMonth->copy(); $date <= $endOfMonth; $date->addDay()) {
            $dates->push($date->copy());
        }

        return view('attendance.list', compact(
            'attendances',
            'dates',
            'currentYm',
            'prevMonth',
            'nextMonth',
            'displayYm'
        ));
    }


    // PG05 勤怠詳細
    public function detail(Attendance $attendance)
    {
        if ($attendance->user_id !== Auth::id()) {
            abort(403);
        }

        // 承認待ちの修正申請があるか
        $hasPendingApplication = Application::where('attendance_id', $attendance->id)
            ->where('status', Application::STATUS_PENDING)
            ->exists();

        return view('attendance.detail', [
            'attendance' => $attendance,
            'hasPendingApplication' => $hasPendingApplication,
        ]);
    }

    // 今日の勤怠を取得する共通メソッド
    private function findTodayAttendance(int $userId): ?Attendance
    {
        return Attendance::where('user_id', $userId)
            ->whereDate('date', today())
            ->first();
    }

}
