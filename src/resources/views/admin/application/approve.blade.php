@extends('admin.layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/detail.css') }}">
@endsection

@section('content')
<div class="container black-bold-letter">
    <h1 class="page-heading">申請詳細</h1>

    @include('attendance.partials.detail-form', [
        'user' => $application->user,
        'date' => $application->date,
        'start_time' => $application->requested_start_time,
        'end_time' => $application->requested_end_time,
        'breaks' => $application->requested_breaks,
        'comment' => $application->comment,
    ])

    <form method="POST" action="{{ route('admin.application.approve', $application->id) }}">
        @csrf
        @method('PUT')

        @if ($application->isPending())
            <button class="form__btn">承認</button>
        @else
            <button class="form__btn--disabled" disabled>承認済み</button>
        @endif
    </form>
</div>
@endsection
