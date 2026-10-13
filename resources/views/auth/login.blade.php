<x-layouts.app title="Đăng nhập" main-class="flex min-h-screen flex-col items-center justify-center py-10">
    <div class="flex w-full max-w-110 flex-col gap-6">
        <a href="{{ route('home') }}" class="self-center text-3xl leading-tight font-bold text-primary-text no-underline hover:text-primary-text hover:no-underline">FunEnglish</a>

        <div class="flex flex-col gap-6 rounded-lg bg-surface-card px-8 py-10 shadow-card">
            <div class="flex flex-col gap-2 text-center">
                <h1 class="type-h1 text-fg-strong">Đăng nhập</h1>
                <p class="type-body text-fg-muted">Chào mừng bạn quay lại FunEnglish!</p>
            </div>

            @if ($errors->has('credentials'))
                <x-alert tone="danger">{{ $errors->first('credentials') }}</x-alert>
            @endif

            <x-google-sign-in />

            <form method="POST" action="{{ route('login.store') }}" novalidate data-auth-form="login" class="flex flex-col gap-5">
                @csrf

                <x-auth-field name="email" label="Email" type="email" placeholder="ten@email.com" autocomplete="email" />

                <x-auth-field name="password" label="Mật khẩu" placeholder="Nhập mật khẩu" autocomplete="current-password" password toggle>
                    <x-slot:aside>
                        <a href="#" class="type-body-sm">Quên mật khẩu?</a>
                    </x-slot:aside>
                </x-auth-field>

                <label class="flex cursor-pointer items-center gap-3 type-body text-fg-body select-none">
                    <x-checkbox name="remember" value="1" :checked="old('remember', ! old('email'))" />
                    Ghi nhớ đăng nhập
                </label>

                <x-button type="submit" size="lg" full-width data-submit>Đăng nhập</x-button>
            </form>
        </div>

        <p class="text-center type-body text-fg-body">Chưa có tài khoản? <a href="{{ route('register') }}" class="font-semibold">Đăng ký ngay</a></p>
    </div>

    <x-slot:scripts>
        @vite('resources/js/auth.js')
    </x-slot:scripts>
</x-layouts.app>
