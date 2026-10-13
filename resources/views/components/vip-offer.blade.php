{{-- Offer to join the membership instead of buying a single course: all courses, no limit, for the plan's monthly price. --}}
@props([
    'plan',
])

<div {{ $attributes->class(['flex flex-wrap items-center justify-between gap-4 rounded-md border border-line-default bg-surface-muted px-5 py-4']) }}>
    <div class="flex min-w-0 flex-[1_1_320px] flex-col gap-1">
        <p class="type-label-lg text-fg-strong">Đăng ký thành viên VIP</p>
        <p class="type-body text-pretty text-fg-body">
            Học tất cả các khóa học không giới hạn, chỉ với <b class="text-danger-text">{{ number_format($plan->monthly_price, 0, ',', '.') }}₫</b>/tháng.
        </p>
    </div>

    <x-button variant="secondary" icon-left="user-plus" :href="route('membership.enroll')">Đăng ký VIP</x-button>
</div>
