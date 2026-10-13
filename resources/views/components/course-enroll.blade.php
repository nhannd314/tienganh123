{{-- The price of a paid course (as a sale when one is shown, see Course::displayDiscountPercent) and its enroll button. --}}
@props([
    'course',
])

<div {{ $attributes->class(['flex flex-col gap-2']) }}>
    <div class="mb-1 flex flex-wrap items-center gap-x-3 gap-y-1">
        @if ($course->originalPriceLabel())
            <del class="type-body-lg text-fg-muted">{{ $course->originalPriceLabel() }}</del>
            <span class="rounded-sm bg-danger px-2 py-0.5 text-sm font-semibold text-on-danger">-{{ $course->displayDiscountPercent() }}%</span>
        @endif
        <p class="type-h2 text-danger-text">{{ $course->priceLabel() }}</p>
    </div>
    <x-button variant="secondary" size="lg" icon-left="user-plus" full-width :href="route('courses.enroll', $course)">Đăng ký khóa học</x-button>
    @guest
        <p class="mt-2 text-center type-body-sm text-fg-muted">
            Bạn cần <a href="{{ route('login') }}" class="font-semibold text-fg-link underline">Đăng nhập</a> để đăng ký khóa học
        </p>
    @endguest
</div>
