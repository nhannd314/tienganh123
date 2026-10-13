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
            <p class="type-body-lg text-fg-muted">{{ $course->title }} · {{ $course->priceLabel() }} cho mỗi tháng học</p>
        </div>

        @if ($isMember)
            <x-alert tone="success" title="Bạn đang là thành viên">Gói thành viên đã mở tất cả các khóa học, bạn không cần đăng ký riêng khóa này.</x-alert>
        @else
            @if ($activeUntil)
                <x-alert tone="info" title="Bạn đang học khóa này">Đăng ký hiện tại có hiệu lực đến {{ $activeUntil->format('d/m/Y') }}. Chuyển khoản bên dưới để gia hạn thêm một tháng.</x-alert>
            @endif

            <x-bank-transfer :payment="$payment" :bank="$bank" :qr-url="$qrUrl" paid-message="Khóa học đã được mở, đang chuyển bạn sang trang khóa học…" />

            @if ($plan)
                <x-vip-offer :plan="$plan" />
            @endif
        @endif

        <div>
            <x-button variant="outline" icon-left="arrow-left" :href="route('courses.show', $course)">Về trang khóa học</x-button>
        </div>
    </section>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
