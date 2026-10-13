@php
    $menu = [
        'achievements' => ['label' => 'Thành tích học tập', 'icon' => 'trophy'],
        'info' => ['label' => 'Thông tin cá nhân', 'icon' => 'user-round-pen'],
        'courses' => ['label' => 'Khóa học đã mua', 'icon' => 'shopping-bag'],
    ];
    $courseTones = ['primary', 'secondary', 'success', 'accent'];
    $toneTextClasses = ['primary' => 'text-primary-text', 'secondary' => 'text-secondary-text', 'success' => 'text-success-text', 'accent' => 'text-accent-text'];
@endphp

<x-layouts.app :title="'Hồ sơ của bạn'" main-class="flex flex-col gap-6 py-10">
    <x-slot:header>
        <x-site-header />
    </x-slot:header>

    <h1 class="type-h1 text-fg-strong">Hồ sơ của bạn</h1>

    <div class="flex flex-wrap items-start gap-6">
        <aside class="flex flex-[1_1_360px] flex-col gap-5 rounded-lg border border-line-subtle bg-surface-card p-5 shadow-card max-[900px]:max-w-none min-[900px]:max-w-90">
            <div class="flex items-center gap-4">
                @if ($user->avatar_url)
                    <img src="{{ $user->avatar_url }}" alt="" class="size-16 flex-none rounded-full border-2 border-primary-border object-cover">
                @else
                    <span class="flex size-16 flex-none items-center justify-center rounded-full border-2 border-primary-border bg-primary-bg text-2xl font-semibold text-primary-text">{{ $user->initial() }}</span>
                @endif
                <div class="flex min-w-0 flex-col gap-1">
                    <b class="type-h3 truncate text-fg-strong">{{ $user->name }}</b>
                </div>
            </div>

            <nav aria-label="Hồ sơ" class="flex flex-col gap-1 border-t border-line-subtle pt-4 max-[900px]:flex-row max-[900px]:flex-wrap">
                @foreach ($menu as $id => $item)
                    <a
                        href="{{ route('profile.show', ['tab' => $id]) }}"
                        @if ($id === $tab) aria-current="page" @endif
                        @class([
                            'flex h-14 items-center gap-3 rounded-md px-4 no-underline transition-interactive hover:no-underline',
                            'bg-primary-bg type-label-lg text-primary-text hover:text-primary-text' => $id === $tab,
                            'type-body-lg text-fg-body hover:bg-surface-hover hover:text-fg-body' => $id !== $tab,
                        ])
                    >
                        <x-icon :name="$item['icon']" size="lg" :class="$id === $tab ? 'text-primary' : 'text-fg-muted'" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <form method="POST" action="{{ route('logout') }}" class="border-t border-line-subtle pt-4">
                @csrf
                <button type="submit" class="flex h-14 w-full cursor-pointer items-center gap-3 rounded-md px-4 text-base text-danger-text transition-interactive hover:bg-danger-bg">
                    <x-icon name="log-out" size="lg" />
                    Đăng xuất
                </button>
            </form>
        </aside>

        <div class="flex min-w-[min(100%,560px)] flex-[999_1_560px] flex-col gap-6">
            @if ($tab === 'achievements')
                <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,200px),1fr))] gap-4">
                    @foreach ([
                        ['icon' => 'book-check', 'label' => 'Bài học đã hoàn thành', 'value' => $stats['lessons'], 'tone' => 'primary'],
                        ['icon' => 'book-a', 'label' => 'Từ vựng đã học', 'value' => $stats['words'], 'tone' => 'secondary'],
                        ['icon' => 'star', 'label' => 'Tổng điểm', 'value' => $stats['score'], 'tone' => 'accent'],
                    ] as $stat)
                        <x-card padding="none" class="flex flex-col gap-3 p-5">
                            <span @class([
                                'flex size-12 items-center justify-center rounded-full',
                                'bg-primary-bg text-primary' => $stat['tone'] === 'primary',
                                'bg-secondary-bg text-secondary' => $stat['tone'] === 'secondary',
                                'bg-accent-bg text-accent-text' => $stat['tone'] === 'accent',
                            ])>
                                <x-icon :name="$stat['icon']" size="lg" />
                            </span>
                            <b class="text-4xl leading-tight font-semibold text-fg-strong tabular-nums">{{ number_format($stat['value'], 0, ',', '.') }}</b>
                            <span class="type-body-sm text-fg-muted">{{ $stat['label'] }}</span>
                        </x-card>
                    @endforeach
                </div>

                <x-card class="flex flex-col gap-5">
                    <h2 class="type-h2 text-fg-strong">Tiến độ khóa học</h2>

                    @forelse ($courseProgress as $progress)
                        @php $tone = $courseTones[$loop->index % count($courseTones)] @endphp
                        <div class="flex flex-col gap-2">
                            <div class="flex items-baseline justify-between gap-4">
                                <a href="{{ route('courses.show', $progress['course']) }}" class="type-label-lg text-fg-strong no-underline hover:text-primary hover:no-underline">{{ $progress['course']->title }}</a>
                                <span class="type-label {{ $toneTextClasses[$tone] }}">{{ $progress['percent'] }}%</span>
                            </div>
                            <x-progress-bar :value="$progress['percent']" :tone="$tone" size="md" :label="$progress['course']->title" />
                            <span class="type-body-sm text-fg-muted">
                                {{ $progress['done'] > 0 ? $progress['done'].'/'.$progress['total'].' bài học' : 'Chưa bắt đầu' }}
                            </span>
                        </div>
                    @empty
                        <p class="type-body text-fg-muted">Bạn chưa tham gia khóa học nào. <a href="{{ route('courses.index') }}">Xem các khóa học</a> để bắt đầu nhé!</p>
                    @endforelse
                </x-card>

                <x-card class="flex flex-col gap-4">
                    <h2 class="type-h2 text-fg-strong">Kết quả ôn tập gần đây</h2>

                    @if ($recentReviews->isEmpty())
                        <p class="type-body text-fg-muted">Bạn chưa làm bài ôn tập nào.</p>
                    @else
                        <ul class="flex flex-col">
                            @foreach ($recentReviews as $review)
                                @php
                                    $score = $review->total > 0 ? (int) round($review->correct / $review->total * 10) : 0;
                                    $percent = $lessonPercents->get($review->lesson_id, 0);
                                @endphp
                                <li class="grid grid-cols-[1fr_auto] items-center gap-4 border-b border-line-subtle py-4 last:border-b-0">
                                    <div class="flex min-w-0 flex-col gap-1">
                                        <b class="type-label-lg truncate text-fg-strong">{{ $review->lesson->title }}</b>
                                        <span class="type-body-sm text-fg-muted">Phần {{ $review->step }} · {{ $review->total }} câu</span>
                                        <div class="mt-1 flex items-center gap-3">
                                            <x-progress-bar :value="$percent" :tone="$percent === 100 ? 'success' : 'primary'" class="w-full max-w-105" :label="'Hoàn thành '.$percent.'%'" />
                                            <span class="min-w-10 type-label-sm text-fg-muted">{{ $percent }}% hoàn thành</span>
                                        </div>
                                    </div>
                                    <x-pill :tone="$score >= 8 ? 'success' : ($score >= 6 ? 'warning' : 'danger')" :icon="$score >= 8 ? 'circle-check' : 'star'">{{ $review->correct }}/{{ $review->total }}</x-pill>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>
            @elseif ($tab === 'info')
                @php
                    $fields = [
                        'name' => ['label' => 'Họ và tên', 'value' => $user->name, 'placeholder' => 'Ví dụ: Nguyễn Minh An'],
                        'email' => ['label' => 'Email', 'value' => $user->email, 'readonly' => true],
                        'phone' => ['label' => 'Số điện thoại', 'value' => $user->phone, 'placeholder' => '09xxxxxxxx'],
                        'birthday' => ['label' => 'Ngày sinh', 'value' => $user->birthday?->format('d/m/Y'), 'placeholder' => 'dd/mm/yyyy'],
                        'grade' => ['label' => 'Lớp', 'value' => $user->grade, 'placeholder' => 'Ví dụ: Lớp 3'],
                        'school' => ['label' => 'Trường', 'value' => $user->school, 'placeholder' => 'Tên trường của bạn'],
                        'parent_email' => ['label' => 'Email phụ huynh', 'value' => $user->parent_email, 'placeholder' => 'ten@email.com'],
                        'parent_phone' => ['label' => 'Số điện thoại phụ huynh', 'value' => $user->parent_phone, 'placeholder' => '09xxxxxxxx'],
                    ];
                @endphp

                @if (session('status'))
                    <x-alert tone="success" title="Đã lưu">{{ session('status') }}</x-alert>
                @endif

                <x-card class="flex flex-col gap-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <h2 class="type-h2 text-fg-strong">Thông tin cá nhân</h2>
                        @unless ($isEditing)
                            <x-button variant="outline" icon-left="pencil" :href="route('profile.show', ['tab' => 'info', 'edit' => 1])">Chỉnh sửa</x-button>
                        @endunless
                    </div>

                    @if ($isEditing)
                        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="flex flex-col gap-6">
                            @csrf
                            @method('PUT')

                            <div class="flex flex-wrap items-center gap-4">
                                <div data-avatar-preview class="flex-none">
                                    @if ($user->avatar_url)
                                        <img src="{{ $user->avatar_url }}" alt="" class="size-16 flex-none rounded-full border-2 border-primary-border object-cover">
                                    @else
                                        <span class="flex size-16 flex-none items-center justify-center rounded-full border-2 border-primary-border bg-primary-bg text-2xl font-semibold text-primary-text">{{ $user->initial() }}</span>
                                    @endif
                                </div>
                                <label class="grid gap-1">
                                    <span class="type-label text-fg-body">Ảnh đại diện</span>
                                    <input type="file" name="avatar" data-avatar-input accept="image/jpeg,image/png,image/webp" class="type-body-sm text-fg-body file:mr-3 file:cursor-pointer file:rounded-sm file:border-0 file:bg-primary-bg file:px-4 file:py-2 file:type-label-sm file:text-primary-text">
                                    <span @class(['type-body-sm', 'text-danger-text' => $errors->has('avatar'), 'text-fg-muted' => ! $errors->has('avatar')])>{{ $errors->first('avatar') ?: 'JPG, PNG hoặc WebP. Ảnh được tự cắt vuông và thu nhỏ.' }}</span>
                                </label>
                            </div>

                            <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,260px),1fr))] gap-x-6 gap-y-5">
                                @foreach (collect($fields)->reject(fn (array $field): bool => $field['readonly'] ?? false) as $name => $field)
                                    <x-text-input
                                        :name="$name"
                                        :label="$field['label']"
                                        :placeholder="$field['placeholder']"
                                        :value="old($name, $field['value'])"
                                        :state="$errors->has($name) ? 'wrong' : null"
                                        :hint="$errors->first($name)"
                                    />
                                @endforeach
                            </div>

                            <div class="flex flex-wrap justify-end gap-3">
                                <x-button variant="outline" :href="route('profile.show', ['tab' => 'info'])">Hủy</x-button>
                                <x-button type="submit" icon-left="check">Lưu thay đổi</x-button>
                            </div>
                        </form>
                    @else
                        <dl class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,260px),1fr))] gap-x-6 gap-y-5">
                            @foreach ($fields as $field)
                                <div class="flex flex-col gap-1 border-b border-line-subtle pb-4">
                                    <dt class="type-body-sm text-fg-muted">{{ $field['label'] }}</dt>
                                    <dd class="type-body-lg break-words text-fg-strong">{{ filled($field['value']) ? $field['value'] : '—' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                </x-card>

                <x-card>
                    <details class="group" {{ $errors->hasAny(['current_password', 'password']) ? 'open' : '' }}>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 [&::-webkit-details-marker]:hidden">
                            <h2 class="type-h2 text-fg-strong">Đổi mật khẩu</h2>
                            <x-icon name="chevron-down" size="lg" class="text-fg-muted transition-transform group-open:rotate-180" />
                        </summary>

                        <form method="POST" action="{{ route('profile.password.update') }}" class="flex flex-col gap-6 pt-8">
                            @csrf
                            @method('PUT')

                            <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,260px),1fr))] items-start gap-x-6 gap-y-5">
                                @if (blank($user->google_id))
                                    <x-text-input type="password" name="current_password" label="Mật khẩu hiện tại" autocomplete="current-password" :state="$errors->has('current_password') ? 'wrong' : null" :hint="$errors->first('current_password')" />
                                @endif
                                <x-text-input type="password" name="password" label="Mật khẩu mới" autocomplete="new-password" :state="$errors->has('password') ? 'wrong' : null" :hint="$errors->first('password') ?: 'Ít nhất 6 ký tự.'" />
                                <x-text-input type="password" name="password_confirmation" label="Nhập lại mật khẩu mới" autocomplete="new-password" />
                            </div>

                            <div class="flex justify-end">
                                <x-button type="submit" icon-left="check">Đổi mật khẩu</x-button>
                            </div>
                        </form>
                    </details>
                </x-card>
            @elseif ($tab === 'courses')
                <h2 class="type-h2 text-fg-strong">Khóa học đã mua</h2>

                @forelse ($subscriptions as $subscription)
                    @continue(! $subscription->subscribable)
                    @php $course = $subscription->subscribable @endphp
                    <x-card padding="none" class="flex flex-wrap items-center gap-4 px-5 py-4">
                        <div class="flex min-w-60 flex-[1_1_240px] flex-col gap-1">
                            <b class="type-label-lg text-fg-strong">{{ $course->title }}</b>
                            <span class="type-body-sm text-fg-muted">Mua ngày {{ $subscription->starts_at->format('d/m/Y') }}</span>
                        </div>
                        <span @class([
                            'inline-flex h-6 items-center rounded-sm border px-2 type-label-sm whitespace-nowrap',
                            'border-success-border bg-success-bg text-success-text' => $subscription->price_paid === 0,
                            'border-primary-border bg-primary-bg text-primary-text' => $subscription->price_paid !== 0,
                        ])>{{ $subscription->price_paid === 0 ? 'Miễn phí' : number_format($subscription->price_paid, 0, ',', '.').'₫' }}</span>
                        <x-button variant="outline" size="sm" icon-right="arrow-right" :href="route('courses.show', $course)">Vào học</x-button>
                    </x-card>
                @empty
                    <x-card class="flex flex-col items-center gap-3 text-center">
                        <x-icon name="shopping-bag" size="xl" class="text-primary" />
                        <p class="type-body text-fg-muted">Bạn chưa mua khóa học nào.</p>
                        <x-button variant="outline" :href="route('courses.index')">Xem khóa học</x-button>
                    </x-card>
                @endforelse
            @endif
        </div>
    </div>
</x-layouts.app>
