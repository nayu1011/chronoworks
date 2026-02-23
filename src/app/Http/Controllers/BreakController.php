<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class BreakController extends Controller
{
    /**
     * 休憩開始
     */
    public function start(): RedirectResponse
    {
        $user = Auth::user();

        // 本日の勤怠取得
        $attendance = $this->findTodayAttendance($user->id);

        // 勤怠が存在しない場合
        if (! $attendance) {
            return $this->redirectToIndex();
        }

        // 退勤済みの場合
        if ($attendance->end_time) {
            return $this->redirectToIndex();
        }

        // すでに休憩中の場合（二重休憩防止）
        if ($attendance->attendanceBreaks()->whereNull('end_time')->exists()) {
            return $this->redirectToIndex();
        }

        $attendance->attendanceBreaks()->create([
            'start_time' => now(),
        ]);

        return $this->redirectToIndex();
    }

    /**
     * 休憩終了
     */
    public function end(): RedirectResponse
    {
        $user = Auth::user();
        $attendance = $this->findTodayAttendance($user->id);

        // 勤怠が存在しない場合
        if (! $attendance) {
            return $this->redirectToIndex();
        }

        // 退勤済みの場合（念のため）
        if ($attendance->end_time) {
            return $this->redirectToIndex();
        }

        // 休憩中のレコード取得
        $attendanceBreak = $attendance->attendanceBreaks()
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();

        if (! $attendanceBreak) {
            return $this->redirectToIndex();
        }

        $attendanceBreak->update([
            'end_time' => now(),
        ]);

        return $this->redirectToIndex();
    }

    // 今日の勤怠を取得する共通メソッド
    private function findTodayAttendance(int $userId): ?Attendance
    {
        return Attendance::where('user_id', $userId)
            ->whereDate('date', today())
            ->first();
    }

    // 勤怠画面にリダイレクトする共通メソッド
    private function redirectToIndex(): RedirectResponse
    {
        return redirect()->route('attendance.index');
    }

}
