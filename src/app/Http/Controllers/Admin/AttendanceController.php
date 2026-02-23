<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Http\Requests\AttendanceCorrectionStoreRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    // PG08 勤怠一覧（管理者）
    public function list(Request $request)
    {
        // 表示対象日（date=YYYY-MM-DD を想定）
        $date = Carbon::parse($request->input('date', today()->toDateString()));

        // date picker 用
        $currentDate = $date->toDateString(); // yyyy-mm-dd

        // 見出し表示用（0埋めしないのでn/jを使う）
        $displayYmd = $date->format('Y年n月j日'); // yyyy年n月j日

        // 管理者用：その日の全ユーザー勤怠
        $attendances = Attendance::with('user')
            ->whereDate('date', $date)
            ->orderBy('user_id')
            ->get();

        // 前日・翌日
        $prevDate = $date->copy()->subDay()->toDateString();
        $nextDate = $date->copy()->addDay()->toDateString();

        return view('admin.attendance.list', compact(
            'date',
            'currentDate',
            'displayYmd',
            'prevDate',
            'nextDate',
            'attendances'
        ));
    }

    // PG09 勤怠詳細（管理者）
    public function detail(Attendance $attendance)
    {
        return view('admin.attendance.detail', compact('attendance'));
    }

    // 勤怠情報直接修正（管理者）
    public function update(AttendanceCorrectionStoreRequest $request, Attendance $attendance)
    {
        DB::transaction(function () use ($request, $attendance) {

            $date = $request->input('date');

            $startDateTime = Carbon::createFromFormat(
                'Y-m-d H:i',
                "{$date} {$request->start_time}"
            );

            $endDateTime = Carbon::createFromFormat(
                'Y-m-d H:i',
                "{$date} {$request->end_time}"
            );

            // 勤怠本体更新（datetimeに変換）
            $attendance->update([
                'start_time' => $startDateTime,
                'end_time'   => $endDateTime,
                'comment'    => $request->comment,
            ]);

            // 休憩削除
            $attendance->attendanceBreaks()->delete();

            // 休憩再登録
            foreach ($request->input('breaks', []) as $break) {

                $startTime = $break['start_time'] ?? null;
                $endTime   = $break['end_time'] ?? null;

                // 片方でも空なら休憩なしとみなす
                if (empty($startTime) || empty($endTime)) {
                    continue;
                }

                $attendance->attendanceBreaks()->create([
                    'start_time' => Carbon::createFromFormat('Y-m-d H:i', "{$date} {$startTime}"),
                    'end_time'   => Carbon::createFromFormat('Y-m-d H:i', "{$date} {$endTime}"),
                ]);
            }
        });

        return redirect()->route('admin.attendance.detail', ['attendance' => $attendance->id]);
    }

    // PG10 スタッフ一覧（管理者）
    public function staffList()
    {
        $staffs = User::query()
            ->where('role', 'user')
            ->orderBy('id')
            ->get();

        return view('admin.staff.list', [
            'staffs' => $staffs,
        ]);
    }

    // PG11 スタッフ別勤怠一覧
    public function staffAttendanceList(Request $request, User $staff)
    {
        $currentYm = $request->input('month', now()->format('Y-m'));

        $current = Carbon::createFromFormat('Y-m', $currentYm);

        $prevMonth = $current->copy()->subMonth()->format('Y-m');
        $nextMonth = $current->copy()->addMonth()->format('Y-m');
        $displayYm = $current->format('Y/m');

        $startOfMonth = Carbon::createFromFormat('Y-m', $currentYm)->startOfMonth();
        $endOfMonth   = $startOfMonth->copy()->endOfMonth();

        $attendances = Attendance::with('attendanceBreaks')
            ->where('user_id', $staff->id)
            ->whereBetween('date', [
                $startOfMonth->toDateString(),
                $endOfMonth->toDateString()
            ])
            ->get()
            ->keyBy(fn ($a) => $a->date->toDateString());

        $dates = collect();
        for ($date = $startOfMonth->copy(); $date <= $endOfMonth; $date->addDay()) {
            $dates->push($date->copy());
        }

        return view('admin.staff.detail', compact(
            'staff',
            'attendances',
            'dates',
            'currentYm',
            'prevMonth',
            'nextMonth',
            'displayYm'
        ));
    }

    // CSV出力
    public function exportStaffMonthlyCsv(Request $request, User $staff): StreamedResponse
    {
        $month = $request->query('month', now()->format('Y-m'));

        $from = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->startOfDay();
        $to   = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->startOfDay();

        // 対象月の勤怠（必要なリレーションを読み込み）
        $attendances = Attendance::with('attendanceBreaks')
            ->where('user_id', $staff->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date')
            ->get()
            ->keyBy(fn ($attendance) => $attendance->date->toDateString());

        $fileName = sprintf(
            '%s_出勤一覧_%s月.csv',
            str_replace(' ', '', $staff->name),
            str_replace('-', '年', $month)
        );

        $response = response()->streamDownload(function () use ($attendances, $from, $to) {
            $out = fopen('php://output', 'w');

            $write = function (array $row) use ($out) {
                $encoded = array_map(function ($v) {
                    $v = (string)($v ?? '');
                    return mb_convert_encoding($v, 'SJIS-win', 'UTF-8');
                }, $row);

                fputcsv($out, $encoded);
            };

            // ヘッダ
            $write(['日付', '出勤', '退勤', '休憩', '合計']);

            // 月の全日付を回す
            $period = CarbonPeriod::create($from, $to);

            foreach ($period as $date) {
                $key = $date->toDateString();
                /** @var \App\Models\Attendance|null $attendance */
                $attendance = $attendances->get($key);

                if ($attendance) {
                    $start = $attendance->start_time ? $attendance->start_time->format('H:i') : '';
                    $end   = $attendance->end_time   ? $attendance->end_time->format('H:i')   : '';

                    // 休憩合計（分）
                    $breakMinutes = 0;
                    foreach ($attendance->attendanceBreaks as $b) {
                        if ($b->start_time && $b->end_time) {
                            $breakMinutes += $b->end_time->diffInMinutes($b->start_time);
                        }
                    }

                    // 勤務時間（分）※ start/end が揃ってるときだけ
                    $workMinutes = '';
                    if ($attendance->start_time && $attendance->end_time) {
                        $workMinutes = $attendance->end_time->diffInMinutes($attendance->start_time) - $breakMinutes;
                        if ($workMinutes < 0) {
                            $workMinutes = 0;
                        }
                    }

                    $write([
                        $date->format('Y/m/d'),
                        $start,
                        $end,
                        $breakMinutes === 0 ? '' : $this->formatMinutes($breakMinutes),
                        $workMinutes === '' ? '' : $this->formatMinutes($workMinutes),
                    ]);
                } else {
                    // 勤怠レコードが無い日：全部空欄で出す
                    $write([
                        $date->format('Y/m/d'),
                        '',
                        '',
                        '',
                        '',
                    ]);
                }
            }

            fclose($out);
        }, $fileName);

        $response->headers->set('Content-Type', 'text/csv; charset=Shift_JIS');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$fileName}\"");

        return $response;
    }
    /**
     * 分→H:i 形式
     */
    private function formatMinutes(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return sprintf('%d:%02d', $h, $m);
    }
}
