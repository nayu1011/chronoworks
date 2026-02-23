<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AttendanceCorrectionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date'       => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i'],

            'breaks' => ['nullable', 'array'],
            'breaks.*.start_time' => ['nullable', 'date_format:H:i'],
            'breaks.*.end_time'   => ['nullable', 'date_format:H:i'],

            'comment' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required' => '備考を記入してください',
            'start_time.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'end_time.required'   => '出勤時間もしくは退勤時間が不適切な値です',
            'start_time.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'end_time.date_format'   => '出勤時間もしくは退勤時間が不適切な値です',
        ];
    }

    // 時間の前後関係などのバリデーション
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $date = $this->input('date');
            $startTime = $this->input('start_time');
            $endTime   = $this->input('end_time');

            // 必須/形式NGなら rules 側で拾うので、ここでは何もしない（念のため）
            if (empty($date) || empty($startTime) || empty($endTime)) {
                return;
            }

            try {
                $start = Carbon::createFromFormat('Y-m-d H:i', "{$date} {$startTime}");
                $end   = Carbon::createFromFormat('Y-m-d H:i', "{$date} {$endTime}");
            } catch (\Throwable $e) {
                // 念のため（基本ここには来ない想定）
                $validator->errors()->add('start_time', '出勤時間もしくは退勤時間が不適切な値です');
                return;
            }

            // 出勤 > 退勤
            if ($start->gt($end)) {
                $validator->errors()->add('start_time', '出勤時間もしくは退勤時間が不適切な値です');
                return;
            }

            // 休憩の検証（複数休憩）
            $breaks = $this->input('breaks', []);

            foreach ($breaks as $i => $break) {
                $breakStartTime = $break['start_time'] ?? null;
                $breakEndTime   = $break['end_time'] ?? null;

                // ✅ 片方でも空なら無視
                if (empty($breakStartTime) || empty($breakEndTime)) {
                    continue;
                }

                try {
                    $breakStart = Carbon::createFromFormat('Y-m-d H:i', "{$date} {$breakStartTime}");
                    $breakEnd   = Carbon::createFromFormat('Y-m-d H:i', "{$date} {$breakEndTime}");
                } catch (\Throwable $e) {
                    // 形式が壊れてたら start_time 側に載せる
                    $validator->errors()->add("breaks.{$i}.start_time", '休憩時間が不適切な値です');
                    continue;
                }

                // 休憩開始 > 休憩終了
                if ($breakStart->gt($breakEnd)) {
                    $validator->errors()->add("breaks.{$i}.start_time", '休憩時間が不適切な値です');
                    continue;
                }

                // 休憩開始が勤務外
                if ($breakStart->lt($start) || $breakStart->gt($end)) {
                    $validator->errors()->add("breaks.{$i}.start_time", '休憩時間が不適切な値です');
                }

                // ✅ 休憩終了 > 退勤
                if ($breakEnd->gt($end)) {
                    $validator->errors()->add("breaks.{$i}.start_time", '休憩時間もしくは退勤時間が不適切な値です');
                }
            }
        });
    }
}
