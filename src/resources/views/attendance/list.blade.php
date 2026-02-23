@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/list.css') }}">
@endsection

@section('content')
<div class="container">
    <h1 class="page-heading black-bold-letter">勤怠一覧</h1>

    <div class="month-nav">
        <a href="{{ route('attendance.list', ['month' => $prevMonth]) }}">
            ← 前月
        </a>

        <button class="this-month month-picker-trigger" type="button">
            <img class="calendar-icon" src="{{ asset('images/calendar.png') }}" alt="カレンダーアイコン">
            <span class="black-bold-letter">{{ $displayYm }}</span>
            <input type="month" class="month-picker" value="{{ $currentYm }}">
        </button>

        <a href="{{ route('attendance.list', ['month' => $nextMonth]) }}">
            翌月 →
        </a>
    </div>

    @include('attendance.partials.monthly-table', [
        'dates' => $dates,
        'attendances' => $attendances,
        'detailRouteName' => 'attendance.detail',
    ])

</div>
@endsection

@section('js')
    <script>
        (function () {
            const monthPicker = document.querySelector('.month-picker');
            const trigger = document.querySelector('.month-picker-trigger');

            if (trigger && monthPicker) {
                trigger.addEventListener('click', () => {
                    monthPicker.showPicker?.();
                    monthPicker.focus();
                });
            }

            monthPicker?.addEventListener('change', function () {
                location.href = `{{ route('attendance.list') }}?month=${this.value}`;
            });
        })();
    </script>
@endsection
