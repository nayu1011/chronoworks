@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance/index.css') }}">
@endsection

@section('content')
<div class="container attendance-index">

    {{-- ステータス表示 --}}
    <div class="attendance-status">
        @switch($status)
            @case('before_work')
                勤務外
                @break
            @case('working')
                出勤中
                @break
            @case('on_break')
                休憩中
                @break
            @case('after_work')
                退勤済
                @break
        @endswitch
    </div>

    {{-- 日付 --}}
    <div class="attendance-date">
        {{ $today->format('Y年n月j日') }}({{ ['日','月','火','水','木','金','土'][$today->dayOfWeek] }})
    </div>

    {{-- 時刻 --}}
    <div class="attendance-time js-current-time">
        {{ $now->format('H:i') }}
    </div>

    <div class="attendance-actions">
        {{-- 出勤前 --}}
        @if ($status === 'before_work')
            <form method="POST" action="{{ route('attendance.start') }}">
                @csrf
                <button class="attendance-btn" type="submit">出勤</button>
            </form>
        @endif

        {{-- 出勤中 --}}
        @if ($status === 'working')
            <div class="attendance-working">
                <form method="POST" action="{{ route('attendance.end') }}">
                    @csrf
                    <button class="attendance-btn" type="submit">退勤</button>
                </form>

                <form method="POST" action="{{ route('break.start') }}">
                    @csrf
                    <button class="attendance-btn break-btn" type="submit">休憩入</button>
                </form>
            </div>
        @endif

        {{-- 休憩中 --}}
        @if ($status === 'on_break')
            <form method="POST" action="{{ route('break.end') }}">
                @csrf
                <button class="attendance-btn break-btn" type="submit">休憩戻</button>
            </form>
        @endif

        {{-- 退勤後 --}}
        @if ($status === 'after_work')
            <p class="attendance-message">お疲れ様でした。</p>
        @endif
    </div>
</div>
@endsection

@section('js')
<script>
(function () {
    const el = document.querySelector('.js-current-time');
    if (!el) return;

    const pad = (n) => String(n).padStart(2, '0');

    const tick = () => {
        const now = new Date();
        el.textContent = `${pad(now.getHours())}:${pad(now.getMinutes())}`;
    };

    tick();
    setInterval(tick, 1000); // 1秒ごと
})();
</script>
@endsection
