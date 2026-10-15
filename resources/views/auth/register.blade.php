<x-layouts.app title="Đăng ký" main-class="flex min-h-screen flex-col items-center justify-center py-10">
    <div class="flex w-full max-w-110 flex-col gap-6">
        <a href="{{ route('home') }}" aria-label="Học Tiếng Anh — Trang chủ" class="self-center no-underline hover:no-underline"><x-logo :size="64" domain /></a>

        <div class="flex flex-col gap-6 rounded-lg bg-surface-card px-8 py-10 shadow-card">
            <div class="flex flex-col gap-2 text-center">
                <h1 class="type-h1 text-fg-strong">Đăng ký</h1>
                <p class="type-body text-pretty text-fg-muted">Tạo tài khoản để bắt đầu học tiếng Anh cùng FunEnglish nhé!</p>
            </div>

            <x-google-sign-in label="Đăng ký với Google" divider="hoặc đăng ký bằng email" />

            <form method="POST" action="{{ route('register.store') }}" novalidate data-auth-form="register" class="flex flex-col gap-5">
                @csrf

                <x-auth-field name="name" label="Họ và tên" placeholder="Nguyễn Văn An" autocomplete="name" />
                <x-auth-field name="email" label="Email" type="email" placeholder="ten@email.com" autocomplete="email" />
                <x-auth-field name="password" label="Mật khẩu" placeholder="Ít nhất 6 ký tự" autocomplete="new-password" hint="Dùng ít nhất 6 ký tự." password toggle />
                <x-auth-field name="password_confirmation" label="Nhập lại mật khẩu" placeholder="Nhập lại mật khẩu" autocomplete="new-password" password />

                <div class="grid gap-2" data-field="agree">
                    <label class="flex cursor-pointer items-start gap-3 type-body text-pretty text-fg-body">
                        <x-checkbox name="agree" value="1" data-input :checked="old('agree')" />
                        <span>Mình đồng ý với <a href="#">Điều khoản sử dụng</a> và <a href="#">Chính sách bảo mật</a></span>
                    </label>
                    <span data-field-hint @class(['type-body-sm text-danger-text', 'hidden' => ! $errors->has('agree')])>{{ $errors->first('agree') }}</span>
                </div>

                <x-button type="submit" size="lg" full-width data-submit>Tạo tài khoản</x-button>
            </form>
        </div>

        <p class="text-center type-body text-fg-body">Đã có tài khoản? <a href="{{ route('login') }}" class="font-semibold">Đăng nhập</a></p>
    </div>

    <x-slot:scripts>
        @vite('resources/js/auth.js')
    </x-slot:scripts>
</x-layouts.app>
