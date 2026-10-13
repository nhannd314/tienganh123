<x-layouts.app :title="'Đăng ký '.$course->title" main-class="flex flex-col gap-6 pt-6 pb-10">
    <x-slot:header>
        <x-site-header active="courses" />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Khóa học', 'href' => route('courses.index')],
        ['label' => $course->title, 'href' => route('courses.show', $course)],
        ['label' => 'Đăng ký'],
    ]" />

    <section class="mx-auto flex w-full max-w-240 flex-col gap-6 rounded-lg bg-surface-card p-6 shadow-card sm:p-8">
        <div class="flex flex-col gap-2">
            <h1 class="type-h1 text-balance text-fg-strong">Đăng ký khóa học</h1>
            <p class="type-body-lg text-fg-muted">{{ $course->title }}</p>
        </div>

        <h2 class="type-h3 text-fg-strong">Bước 1: Chọn chu kỳ thanh toán</h2>

        <div role="group" aria-label="Chu kỳ đăng ký" class="grid grid-cols-3 gap-2 sm:gap-3">
            @foreach ($cycles as $cycle)
                <a
                    href="{{ $cycle['url'] }}"
                    @if ($cycle['months'] === $months) aria-current="true" @endif
                    @class([
                        'relative flex min-w-0 flex-col gap-1 rounded-md border-2 p-3 no-underline transition-interactive hover:no-underline sm:p-4',
                        'border-primary bg-primary-bg' => $cycle['months'] === $months,
                        'border-line-default bg-surface-card hover:border-line-hover' => $cycle['months'] !== $months,
                    ])
                >
                    <span class="type-label-lg text-fg-strong">{{ $cycle['months'] }} tháng</span>
                    <span class="type-h3 text-danger-text">{{ $cycle['price'] }}</span>
                    @if ($cycle['discount'] > 0)
                        <span class="flex flex-wrap items-center gap-2">
                            <del class="type-body-sm text-fg-muted">{{ $cycle['fullPrice'] }}</del>
                            <span class="rounded-sm bg-danger px-2 py-0.5 text-xs font-semibold text-on-danger">-{{ $cycle['discount'] }}%</span>
                        </span>
                    @endif
                </a>
            @endforeach
        </div>

        <h2 class="mt-2 type-h3 text-fg-strong">Bước 2: Quét mã QR chuyển khoản</h2>

        @if ($activeUntil)
            <x-alert tone="info" title="Bạn đang học khóa này">Đăng ký hiện tại có hiệu lực đến {{ $activeUntil->format('d/m/Y') }}. Chuyển khoản bên dưới để gia hạn thêm {{ $months }} tháng.</x-alert>
        @endif

        <x-bank-transfer :payment="$payment" :bank="$bank" :qr-url="$qrUrl" paid-message="Khóa học đã được mở, đang chuyển bạn sang trang khóa học…" />

        <div>
            <x-button variant="outline" icon-left="arrow-left" :href="route('courses.show', $course)">Về trang khóa học</x-button>
        </div>
    </section>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
