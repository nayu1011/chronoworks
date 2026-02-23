@extends('admin.layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/list.css') }}">
@endsection

@section('content')
<div class="container">
    <h1 class="page-heading black-bold-letter">{{ $staff->name }}さんの勤怠</h1>

    <div class="month-nav">
        <a href="{{ route('admin.attendance.staff', ['staff' => $staff->id, 'month' => $prevMonth]) }}">← 前月</a>

        <div class="this-month" onclick="document.querySelector('.month-picker').showPicker()">
            <img class="calendar-icon" src="{{ asset('images/calendar.png') }}" alt="カレンダーアイコン">
            <span class="black-bold-letter">{{ $displayYm }}</span>
            <input type="month" class="month-picker" value="{{ $currentYm }}" />
        </div>

        <a href="{{ route('admin.attendance.staff', ['staff' => $staff->id, 'month' => $nextMonth]) }}">翌月 →</a>
    </div>

    @include('attendance.partials.monthly-table', [
        'dates' => $dates,
        'attendances' => $attendances,
        'detailRouteName' => 'admin.attendance.detail',
    ])

    <div class="admin-action">
        <button class="form__btn admin-action__btn"
        onclick="location.href='{{ route('admin.attendance.staff.csv', ['staff' => $staff->id, 'month' => $currentYm]) }}'">
            CSV出力
        </button>
    </div>
</div>
@endsection

@section('js')
<script>
document.querySelector('.month-picker').addEventListener('change', function () {
    location.href = "{{ route('admin.attendance.staff', $staff->id) }}?month=" + this.value;
});
</script>
@endsection
