<table class="list-table">
    <thead>
        <tr>
            <th>状態</th>
            <th>名前</th>
            <th>対象日時</th>
            <th>申請理由</th>
            <th>申請日時</th>
            <th class="black-bold-letter">詳細</th>
        </tr>
    </thead>

    <tbody>
        @forelse ($applications as $application)
            <tr>
                <td class="td--nowrap">
                    {{ $application->status === 'pending' ? '承認待ち' : '承認済み' }}
                </td>
                <td class="td--nowrap">
                    {{ $application->applicant->name ?? '-' }}
                </td>
                <td class="td--nowrap">
                    {{ $application->attendance->date?->format('Y/m/d') ?? '-' }}
                </td>
                <td>
                    {{ $application->comment ?? '-' }}
                </td>
                <td class="td--nowrap">
                    {{ $application->created_at?->format('Y/m/d') ?? '-' }}
                </td>
                <td class="black-bold-letter td--nowrap">
                    <a href="{{ route($detailRoute, data_get($application, $detailParam)) }}">
                        詳細
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="td--nowrap"></td>
            </tr>
        @endforelse
    </tbody>
</table>
