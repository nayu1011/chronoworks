@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1 class="page-heading black-bold-letter">申請一覧</h1>

    {{-- タブ --}}
    <div class="application-tabs">
        <a href="{{ route('application.list', ['status' => 'pending']) }}"
        class="tabs__link {{ $status === 'pending' ? 'tabs__link--active' : '' }}">
            承認待ち
        </a>
        <a href="{{ route('application.list', ['status' => 'approved']) }}"
        class="tabs__link {{ $status === 'approved' ? 'tabs__link--active' : '' }}">
            承認済み
        </a>
    </div>

    @include('application.partials.list-table', [
        'applications' => $applications,
        'detailRoute' => 'admin.application.detail',
        'detailParam' => 'id',
    ])

</div>
@endsection
