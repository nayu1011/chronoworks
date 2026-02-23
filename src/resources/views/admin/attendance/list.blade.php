@extends('admin.layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/list.css') }}">
@endsection

@section('content')
<div class="container">
    <h1 class="page-heading black-bold-letter">{{ $displayYmd }}の勤怠</h1>

    <div class="month-nav">
        <a href="{{ route('admin.attendance.list', ['date' => $prevDate ]) }}">
            ← 前日
        </a>
        <div class="this-month" onclick="document.querySelector('.date-picker').showPicker()">
            <img class="calendar-icon" src="{{ asset('images/calendar.png') }}" alt="カレンダーアイコン">
            <span class="black-bold-letter">{{ $date->format('Y/n/j') }}</span>

            <input type="date" class="date-picker" value="{{ $date->toDateString() }}" />
        </div>

        <a href="{{ route('admin.attendance.list', ['date' => $nextDate]) }}">
            翌日 →
        </a>
    </div>

    {{-- 勤怠一覧 --}}
    <table class="list-table">
        <thead>
            <tr>
                <th>名前</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th class="black-bold-letter">詳細</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($attendances as $attendance)
                <tr>
                    <td>{{ $attendance->user->name }}</td>
                    <td>{{ $attendance->start_time?->format('H:i') ?? '-' }}</td>
                    <td>{{ $attendance->end_time?->format('H:i') ?? '-' }}</td>
                    <td>{{ $attendance->break_time_formatted }}</td>
                    <td>{{ $attendance->working_time_formatted }}</td>
                    <td class="black-bold-letter">
                        <a href="{{ route('admin.attendance.detail', $attendance->id) }}">
                            詳細
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6"></td>
                </tr>
            @endforelse
        </tbody>
    </table>

</div>
@endsection

@section('js')
<script>
document.querySelector('.date-picker')?.addEventListener('change', function () {
    location.href = `{{ route('admin.attendance.list') }}?date=${this.value}`;
});
</script>
@endsection

