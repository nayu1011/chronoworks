<table class="list-table">
    <thead>
        <tr>
            <th>日付</th>
            <th>出勤</th>
            <th>退勤</th>
            <th>休憩</th>
            <th>合計</th>
            <th class="black-bold-letter">詳細</th>
        </tr>
    </thead>

    <tbody>
    @foreach ($dates as $date)
        @php
            $attendance = $attendances[$date->toDateString()] ?? null;
        @endphp

        <tr>
            <td>
                {{ $date->format('m/d') }}
                ({{ ['日','月','火','水','木','金','土'][$date->dayOfWeek] }})
            </td>

            @if ($attendance)
                <td>{{ $attendance->start_time?->format('H:i') ?? '-' }}</td>
                <td>{{ $attendance->end_time?->format('H:i') ?? '-' }}</td>
                <td>{{ $attendance->break_time_formatted }}</td>
                <td>{{ $attendance->working_time_formatted }}</td>
                <td>
                    <a class="black-bold-letter" href="{{ route($detailRouteName, $attendance) }}">
                        詳細
                    </a>
                </td>
            @else
                <td colspan="4"></td>
                <td class="black-bold-letter">詳細</td>
            @endif
        </tr>
    @endforeach
    </tbody>
</table>
