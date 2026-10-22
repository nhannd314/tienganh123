<x-layouts.app title="Quên mật khẩu" noindex main-class="flex min-h-screen flex-col items-center justify-center py-10">
    <div class="flex w-full max-w-110 flex-col gap-6">
        <a href="{{ route('home') }}" aria-label="TiếngAnhPro — Trang chủ" class="self-center no-underline hover:no-underline"><x-logo :size="64" domain /></a>

        <div class="flex flex-col gap-6 rounded-lg bg-surface-card px-8 py-10 shadow-card">
            <div class="flex flex-col gap-2 text-center">
                <h1 class="type-h1 text-fg-strong">Quên mật khẩu</h1>
                <p class="type-body text-pretty text-fg-muted">Nhập email đã đăng ký, chúng mình sẽ gửi link để bạn đặt lại mật khẩu.</p>
            </div>

            @if (session('status'))
                <x-alert tone="success">{{ session('status') }}</x-alert>
            @endif

            <form method="POST" action="{{ route('password.email') }}" novalidate data-auth-form="forgot" class="flex flex-col gap-5">
                @csrf

                <x-auth-field name="email" label="Email" type="email" placeholder="ten@email.com" autocomplete="email" />

                <x-button type="submit" size="lg" full-width data-submit>Gửi link đặt lại mật khẩu</x-button>
            </form>
        </div>

        <p class="text-center type-body text-fg-body"><a href="{{ route('login') }}" class="font-semibold">Quay lại đăng nhập</a></p>
    </div>

    <x-slot:scripts>
        @vite('resources/js/auth.js')
    </x-slot:scripts>
</x-layouts.app>
