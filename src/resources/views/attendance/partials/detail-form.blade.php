{{--承認可能かどうかでフォームの表示を切り替えるため、$isApprove変数を用意--}}
@php($isApprove = $isApprove ?? false)
<form class="detail-form" method="POST" action="{{ $action }}">
    @csrf

    {{-- 管理者・一般 共通で使うため --}}
    @isset($method)
        @method($method)
    @endisset

    {{-- 対象の勤怠ID --}}
    {{-- 一般ユーザー（申請用）だけで使用 --}}
    @isset($showAttendanceId)
        <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
    @endisset

    <table class="list-table">
        {{-- 名前 --}}
        <tr>
            <th>名前</th>
            <td><span class="name">{{ $attendance->user->name }}</span></td>
        </tr>

        {{-- 日付 --}}
        <tr>
            <th>日付</th>
            <td class="date-split">
                <span class="date-year">{{ $attendance->date->format('Y') }}年</span>
                <span class="date-md">{{ $attendance->date->month }}月{{ $attendance->date->day }}日</span>
                <input type="hidden" name="date" value="{{ $attendance->date->toDateString() }}">
            </td>
        </tr>

        {{-- 出勤・退勤 --}}
        <tr>
            <th>出勤・退勤</th>
            <td>
                <div class="time-range">
                    <input class="form-input--time"
                        type="time"
                        name="start_time"
                        value="{{ old('start_time', $attendance->start_time?->format('H:i')) }}"
                        {{ $isApprove ? 'readonly' : '' }}
                    >
                    〜
                    <input class="form-input--time"
                        type="time"
                        name="end_time"
                        value="{{ old('end_time', $attendance->end_time?->format('H:i')) }}"
                        {{ $isApprove ? 'readonly' : '' }}
                    >
                </div>
                @error('start_time')
                    <p class="form__error">{{ $message }}</p>
                @enderror
            </td>
        </tr>

        {{-- 既存の休憩 --}}
        @foreach ($attendance->attendanceBreaks as $index => $break)
            <tr>
                <th>{{ $index === 0 ? '休憩' : '休憩 ' . ($index + 1) }}</th>
                <td>
                    <div class="time-range">
                        <input class="form-input--time"
                            type="time"
                            name="breaks[{{ $index }}][start_time]"
                            value="{{ old("breaks.{$index}.start_time", $break->start_time?->format('H:i')) }}"
                            {{ $isApprove ? 'readonly' : '' }}
                        >
                        〜
                        <input class="form-input--time"
                            type="time"
                            name="breaks[{{ $index }}][end_time]"
                            value="{{ old("breaks.{$index}.end_time", $break->end_time?->format('H:i')) }}"
                            {{ $isApprove ? 'readonly' : '' }}
                        >
                    </div>
                    @error("breaks.{$index}.start_time")
                        <p class="form__error">{{ $message }}</p>
                    @enderror
                </td>
            </tr>
        @endforeach

        {{-- 休憩追加（空欄1行） --}}
        <tr>
            <th>休憩{{ $attendance->attendanceBreaks->count() + 1 }}</th>
            <td>
                <div class="time-range">
                    <input class="form-input--time"
                        type="time"
                        name="breaks[new][start_time]"
                        value="{{ old('breaks.new.start_time') }}"
                        {{ $isApprove ? 'readonly' : '' }}
                    >
                    〜
                    <input class="form-input--time"
                        type="time"
                        name="breaks[new][end_time]"
                        value="{{ old('breaks.new.end_time') }}"
                        {{ $isApprove ? 'readonly' : '' }}
                    >
                </div>
                @error("breaks.new.start_time")
                    <p class="form__error">{{ $message }}</p>
                @enderror
            </td>
        </tr>

        {{-- 備考 --}}
        <tr>
            <th>備考</th>
            <td>
                <textarea class="form-input--comment" name="comment" rows="3" cols="40" {{ $isApprove ? 'readonly' : '' }}>{{ old('comment', $attendance->comment) }}</textarea>
                @error('comment')
                    <p class="form__error">{{ $message }}</p>
                @enderror
            </td>
        </tr>
    </table>

    {{-- ボタン制御 --}}
    @if ($canSubmit)
        <button class="form__btn" type="submit">{{ $isApprove ? '承認' : '修正' }}</button>
    @else
        <p class="application-message">
            *承認待ちのため修正はできません
        </p>
    @endif
</form>
