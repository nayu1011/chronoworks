@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/detail.css') }}">
@endsection

@section('content')
<div class="container attendance-detail black-bold-letter {{ $hasPendingApplication ? 'is-applied' : '' }}">
    <h1 class="page-heading">勤怠詳細</h1>

    @include('attendance.partials.detail-form', [
        'attendance' => $attendance,
        'action' => route('application.store'),
        'canSubmit' => ! $hasPendingApplication,
        'showAttendanceId' => true,
    ])
</div>
@endsection
