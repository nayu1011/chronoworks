@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1 class="page-heading black-bold-letter">スタッフ一覧</h1>

    <table class="list-table">
        <thead>
            <tr>
                <th>名前</th>
                <th>メールアドレス</th>
                <th>月次勤怠</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($staffs as $staff)
                <tr>
                    <td class="td--nowrap">
                        {{ $staff->name }}
                    </td>
                    <td class="td--nowrap">
                        {{ $staff->email }}
                    </td>
                    <td class="black-bold-letter td--nowrap">
                        <a href="{{ route('admin.attendance.staff', $staff->id) }}">
                            詳細
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</div>
@endsection
