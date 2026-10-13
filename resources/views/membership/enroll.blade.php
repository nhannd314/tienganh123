<x-layouts.app title="Đăng ký thành viên VIP" main-class="flex flex-col gap-6 pt-6 pb-10">
    <x-slot:header>
        <x-site-header active="courses" />
    </x-slot:header>

    <x-breadcrumbs :items="[
        ['label' => 'Trang chủ', 'href' => route('home')],
        ['label' => 'Khóa học', 'href' => route('courses.index')],
        ['label' => 'Thành viên VIP'],
    ]" />

    <section class="mx-auto flex w-full max-w-240 flex-col gap-6 rounded-lg bg-surface-card p-6 shadow-card sm:p-8">
        <div class="flex flex-col gap-2">
            <h1 class="type-h1 text-balance text-fg-strong">Đăng ký thành viên VIP</h1>
            <p class="type-body-lg text-fg-muted">Học tất cả các khóa học không giới hạn · {{ number_format($plan->monthly_price, 0, ',', '.') }}₫ cho mỗi tháng</p>
        </div>

        <x-bank-transfer :payment="$payment" :bank="$bank" :qr-url="$qrUrl" paid-message="Gói thành viên đã được mở, đang chuyển bạn sang danh sách khóa học…" />

        <div>
            <x-button variant="outline" icon-left="arrow-left" :href="route('courses.index')">Về danh sách khóa học</x-button>
        </div>
    </section>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
