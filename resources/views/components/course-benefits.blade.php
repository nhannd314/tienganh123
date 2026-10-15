{{-- Why the course is worth it: the monthly price (or that it is free) and a checklist of benefits. --}}
@props([
    'course',
])

<div {{ $attributes->class(['flex flex-col gap-3']) }}>
    <p class="type-body text-pretty text-fg-body">
        @if ($course->isFree())
            <b class="text-fg-strong">Hoàn toàn miễn phí</b>, bạn học tiếng Anh vui mà nhớ lâu:
        @else
            Chỉ với {{ $course->priceLabel() }}/tháng, bạn học tiếng Anh vui mà nhớ lâu:
        @endif
    </p>

    <ul class="flex flex-col gap-2">
        @foreach (array_filter([
            'Nhớ bài nhanh hơn, chắc hơn và lâu hơn',
            'Bài học sinh động như trò chơi, bạn hào hứng học mỗi ngày, không còn nhàm chán',
            'Ôn tập lặp lại theo từng bước tăng dần, giúp bạn tự tin và hết sợ tiếng Anh',
            $course->isFree() ? null : 'Hoàn tiền 100% nếu học không hiệu quả',
        ]) as $benefit)
            <li class="flex items-start gap-2 type-body text-pretty text-fg-body">
                <x-icon name="circle-check" class="mt-0.5 shrink-0 text-success-text" />
                {{ $benefit }}
            </li>
        @endforeach
    </ul>
</div>
