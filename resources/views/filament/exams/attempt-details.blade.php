<div class="space-y-4">
    <div class="text-sm">
        <span class="font-medium">{{ $attempt->student_name }}</span> · {{ $attempt->class_name }} ·
        Tổng điểm: <span class="font-semibold">{{ $attempt->score !== null ? number_format($attempt->score, 2) : 'Chưa nộp' }}</span> / 100
    </div>

    @if ($groups === [])
        <p class="text-sm text-gray-500">Chưa có kết quả chi tiết.</p>
    @else
        <table class="w-full border-collapse border border-gray-300 text-sm dark:border-white/20">
            <thead>
                <tr class="bg-gray-50 text-left dark:bg-white/5">
                    <th class="border border-gray-300 px-3 py-2 dark:border-white/20">Phần</th>
                    <th class="border border-gray-300 px-3 py-2 text-right dark:border-white/20">Đúng</th>
                    <th class="border border-gray-300 px-3 py-2 text-right dark:border-white/20">Điểm</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($groups as $group)
                    <tr>
                        <td class="border border-gray-300 px-3 py-2 dark:border-white/20">{{ $group['title'] }}</td>
                        <td class="border border-gray-300 px-3 py-2 text-right dark:border-white/20">{{ $group['correct'] }}/{{ $group['total'] }}</td>
                        <td class="border border-gray-300 px-3 py-2 text-right dark:border-white/20">{{ number_format($group['points'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
