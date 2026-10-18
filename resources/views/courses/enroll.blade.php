<x-layouts.app :title="'Đăng ký '.$course->title" main-class="flex flex-col gap-6 pt-6 pb-10">
    <x-slot:header>
        <x-site-header active="courses" />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Khóa học', 'href' => route('courses.index')],
        ['label' => $course->title, 'href' => route('courses.show', $course)],
        ['label' => 'Đăng ký'],
    ]" class="mx-auto w-full max-w-240" />

    <section class="mx-auto flex w-full max-w-240 flex-col gap-6 rounded-lg bg-surface-card p-6 shadow-card sm:p-8">
        <div class="flex flex-col gap-2">
            <h1 class="type-h1 text-balance text-fg-strong">Đăng ký khóa học</h1>
            <p class="type-body-lg text-fg-muted">{{ $course->title }}</p>
            <p class="type-body text-fg-body">Giá gốc: <span class="font-semibold text-danger-text">{{ $basePrice }}/tháng</span></p>
        </div>

        <h2 class="flex items-center gap-3 type-h3 text-fg-strong">
            <span class="flex size-10 flex-none items-center justify-center rounded-full bg-primary type-label-lg text-on-primary">1</span>
            Chọn chu kỳ thanh toán
        </h2>

        <div role="group" aria-label="Chu kỳ đăng ký" class="grid gap-4 pt-2 sm:grid-cols-3">
            @foreach ($cycles as $cycle)
                @php($isChosen = $cycle['months'] === $months)
                <a
                    href="{{ $cycle['url'] }}"
                    @if ($isChosen) aria-current="true" @endif
                    @class([
                        'relative flex min-w-0 flex-col rounded-lg border-2 px-4 py-3 text-inherit no-underline transition-interactive hover:text-inherit hover:no-underline sm:px-5 sm:py-4',
                        'border-primary bg-primary-bg' => $isChosen,
                        'border-line-subtle bg-surface-card hover:border-line-hover' => ! $isChosen,
                    ])
                >
                    @if ($cycle['isFeatured'])
                        <span class="absolute -top-3.5 right-10 rounded-full bg-secondary px-2.5 py-1 text-xs font-medium whitespace-nowrap text-on-secondary">Tiết kiệm nhất</span>
                    @endif

                    <span class="flex items-center justify-between gap-3">
                        <span class="type-label-lg text-fg-muted">{{ $cycle['months'] }} tháng</span>
                        <span @class([
                            'size-5 flex-none rounded-full border-2',
                            'border-primary bg-primary shadow-[inset_0_0_0_3px_var(--color-primary-bg)]' => $isChosen,
                            'border-line-hover' => ! $isChosen,
                        ])></span>
                    </span>

                    @if ($cycle['monthlyPrice'])
                        <span class="mt-3 type-h1 text-fg-strong">{{ $cycle['monthlyPrice'] }}<span class="type-body font-normal text-fg-muted"> / tháng</span></span>
                    @else
                        <span class="mt-3 type-h1 text-fg-strong">{{ $cycle['price'] }}</span>
                    @endif

                    <span class="mt-2 mb-1 flex flex-wrap items-center gap-x-2 border-t border-line-subtle pt-2 type-body-sm text-fg-muted">
                        Tổng: <span class="font-semibold text-fg-body">{{ $cycle['price'] }}</span>
                        @if ($cycle['discount'] > 0)
                            <del>{{ $cycle['fullPrice'] }}</del>
                        @endif
                    </span>
                    @if ($cycle['discount'] > 0)
                        <span class="type-label-sm text-success-text">Tiết kiệm {{ $cycle['saving'] }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        <h2 class="mt-2 flex items-center gap-3 type-h3 text-fg-strong">
            <span class="flex size-10 flex-none items-center justify-center rounded-full bg-primary type-label-lg text-on-primary">2</span>
            Quét mã QR chuyển khoản
        </h2>

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
