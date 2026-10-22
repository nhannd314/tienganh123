<x-layouts.app title="Đặt lại mật khẩu" noindex main-class="flex min-h-screen flex-col items-center justify-center py-10">
    <div class="flex w-full max-w-110 flex-col gap-6">
        <a href="{{ route('home') }}" aria-label="TiếngAnhPro — Trang chủ" class="self-center no-underline hover:no-underline"><x-logo :size="64" domain /></a>

        <div class="flex flex-col gap-6 rounded-lg bg-surface-card px-8 py-10 shadow-card">
            <div class="flex flex-col gap-2 text-center">
                <h1 class="type-h1 text-fg-strong">Đặt lại mật khẩu</h1>
                <p class="type-body text-pretty text-fg-muted">Chọn mật khẩu mới cho tài khoản của bạn nhé!</p>
            </div>

            @if ($errors->has('credentials'))
                <x-alert tone="danger">
                    {{ $errors->first('credentials') }}
                    <a href="{{ route('password.request') }}" class="font-semibold">Yêu cầu link mới</a>
                </x-alert>
            @endif

            <form method="POST" action="{{ route('password.store') }}" novalidate data-auth-form="reset" class="flex flex-col gap-5">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ old('email', $email) }}">

                <x-auth-field name="password" label="Mật khẩu mới" placeholder="Ít nhất 6 ký tự" autocomplete="new-password" hint="Dùng ít nhất 6 ký tự." password toggle />
                <x-auth-field name="password_confirmation" label="Nhập lại mật khẩu" placeholder="Nhập lại mật khẩu" autocomplete="new-password" password />

                <x-button type="submit" size="lg" full-width data-submit>Đặt lại mật khẩu</x-button>
            </form>
        </div>
    </div>

    <x-slot:scripts>
        @vite('resources/js/auth.js')
    </x-slot:scripts>
</x-layouts.app>
