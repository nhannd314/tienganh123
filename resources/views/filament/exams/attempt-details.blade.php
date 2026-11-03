<div class="space-y-4">
    <div class="text-sm">
        <span class="font-medium">{{ $attempt->student_name }}</span> · {{ $attempt->class_name }} ·
        Tổng điểm: <span class="font-semibold">{{ $attempt->score !== null ? number_format($attempt->score, 2) : 'Chưa nộp' }}</span> / 100
    </div>

    @if ($groups === [])
        <p class="text-sm text-gray-500">Chưa có kết quả chi tiết.</p>
    @else
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b text-left text-gray-500">
                    <th class="py-2 pe-2">Phần</th>
                    <th class="py-2 pe-2 text-right">Đúng</th>
                    <th class="py-2 text-right">Điểm</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($groups as $group)
                    <tr class="border-b">
                        <td class="py-2 pe-2">{{ $group['title'] }}</td>
                        <td class="py-2 pe-2 text-right">{{ $group['correct'] }}/{{ $group['total'] }}</td>
                        <td class="py-2 text-right">{{ number_format($group['points'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
