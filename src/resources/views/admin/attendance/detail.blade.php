@extends('admin.layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/detail.css') }}">
@endsection

@section('content')
<div class="container attendance-detail black-bold-letter">
    <h1 class="page-heading">勤怠詳細</h1>

    @include('attendance.partials.detail-form', [
        'attendance' => $attendance,
        'action' => route('admin.attendance.update', ['attendance' => $attendance->id]),
        'method' => 'PUT',
        'canSubmit' => true,
    ])
</div>
@endsection
