<table class="list-table">
    {{-- 名前 --}}
    <tr>
        <th>名前</th>
        <td><span class="name">{{ $application->applicant->name }}</span></td>
    </tr>

    {{-- 日付 --}}
    <tr>
        <th>日付</th>
        <td class="date-split">
            <span class="date-year">{{ $application->attendance->date->format('Y') }}年</span>
            <span class="date-md">{{ $application->attendance->date->month }}月{{ $application->attendance->date->day }}日</span>
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
                    value="{{ $application->start_time?->format('H:i') }}"
                    readonly
                >
                〜
                <input class="form-input--time"
                    type="time"
                    name="end_time"
                    value="{{ $application->end_time?->format('H:i') }}"
                    readonly
                >
            </div>
        </td>
    </tr>

    {{-- 休憩 --}}
    @foreach ($application->applicationBreaks as $index => $break)
        <tr>
            <th>{{ $index === 0 ? '休憩' : '休憩 ' . ($index + 1) }}</th>
            <td>
                <div class="time-range">
                    <input class="form-input--time"
                        type="time"
                        name="breaks[{{ $index }}][start]"
                        value="{{ $break->start_time?->format('H:i') }}"
                        readonly
                    >
                    〜
                    <input class="form-input--time"
                        type="time"
                        name="breaks[{{ $index }}][end]"
                        value="{{ $break->end_time?->format('H:i') }}"
                        readonly
                    >
                </div>
            </td>
        </tr>
    @endforeach

    {{-- 備考 --}}
    <tr>
        <th>備考</th>
        <td>
            <textarea
                class="form-input--comment"
                name="comment"
                rows="3"
                cols="40"
                readonly
            >{{ $application->comment }}
            </textarea>
        </td>
    </tr>
</table>
