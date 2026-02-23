@extends('admin.layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/detail.css') }}">
@endsection

@section('content')
<div class="container attendance-detail black-bold-letter is-applied">
    <h1 class="page-heading">申請詳細</h1>

    @include('application.partials.detail-form', [
        'application' => $application,
    ])

    @if ($application->status === 'pending')
        <p class="application-message">
            *承認待ちのため修正はできません
        </p>
        <div class="admin-action">
            <form method="POST" action="{{ route('admin.application.approve', $application) }}">
                @csrf
                <button class="form__btn admin-action__btn">承認する</button>
            </form>
        </div>
    @else
        <div class="admin-action">
            <button class="form__btn admin-action__btn form__btn--disabled" disabled>承認済み</button>
        </div>
    @endif
</div>
@endsection
