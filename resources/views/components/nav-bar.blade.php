{{--
    Site header: brand mark, main navigation, search and account.
    `items` is a list of ['id' => ..., 'label' => ..., 'icon' => ..., 'href' => ...]; `active` is the current item id.
    Below xl (1280px) it collapses into a sticky bar with search and menu toggles (see resources/js/app.js).
--}}
@props([
    'items' => [],
    'active' => null,
    'homeHref' => '/',
    'avatarSrc' => null,
])

<header {{ $attributes->class(['sticky top-0 z-20 border-b border-line-subtle bg-surface-header shadow-xs xl:static']) }}>
    {{-- Desktop --}}
    <div class="mx-auto hidden h-20 max-w-layout items-center gap-10 px-10 xl:flex">
        <a href="{{ $homeHref }}" aria-label="Học Tiếng Anh — Trang chủ" class="flex flex-none items-center no-underline hover:no-underline">
            <x-logo :size="48" />
        </a>

        <nav aria-label="Chính" class="flex min-w-0 flex-1 gap-1">
            @foreach ($items as $item)
                @php $isActive = $item['id'] === $active; @endphp
                <a
                    href="{{ $item['href'] ?? '#' }}"
                    @if ($isActive) aria-current="page" @endif
                    @class([
                        'flex h-12 items-center gap-2 rounded-full px-4 type-label whitespace-nowrap no-underline transition-interactive hover:no-underline active:translate-y-px',
                        'bg-primary-bg text-primary-text hover:text-primary-text' => $isActive,
                        'text-fg-body hover:bg-surface-hover hover:text-fg-body' => ! $isActive,
                    ])
                >
                    @if (! empty($item['icon']))
                        <x-icon :name="$item['icon']" size="md" :class="$isActive ? 'text-primary' : 'text-fg-muted'" />
                    @endif
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="flex flex-none items-center gap-3">
            <form method="GET" action="{{ route('search') }}" role="search">
                <x-search-input name="q" :value="request('q')" shape="pill" />
            </form>

            @if ($avatarSrc)
                <button type="button" aria-label="Tài khoản" class="flex cursor-pointer rounded-full">
                    <img src="{{ $avatarSrc }}" alt="" class="size-12 rounded-full object-cover">
                </button>
            @elseif (auth()->check())
                @php
                    $accountUser = auth()->user();
                    $accountMembership = $accountUser->activeMembership();
                    $accountLinks = [
                        ['label' => 'Thành tích học tập', 'icon' => 'trophy', 'href' => route('profile.show')],
                        ['label' => 'Thông tin cá nhân', 'icon' => 'user-round-pen', 'href' => route('profile.show', ['tab' => 'info'])],
                        ['label' => 'Khóa học đã mua', 'icon' => 'shopping-bag', 'href' => route('profile.show', ['tab' => 'courses'])],
                    ];
                @endphp
                <div class="relative">
                    <button
                        type="button"
                        aria-label="Tài khoản của {{ $accountUser->name }}"
                        aria-expanded="false"
                        aria-controls="nav-account-menu"
                        data-toggle="nav-account-menu"
                        class="flex size-12 cursor-pointer items-center justify-center rounded-full bg-primary-bg text-xl font-semibold text-primary-text transition-interactive hover:bg-primary-bg-hover aria-expanded:ring-2 aria-expanded:ring-primary aria-expanded:ring-offset-2"
                    >
                        @if ($accountUser->avatar_url)
                            <img src="{{ $accountUser->avatar_url }}" alt="" class="size-full rounded-full object-cover">
                        @else
                            {{ $accountUser->initial() }}
                        @endif
                    </button>

                    <div
                        id="nav-account-menu"
                        hidden
                        data-toggle-outside
                        class="absolute top-full right-0 z-30 mt-2 flex w-64 flex-col gap-1 rounded-lg border border-line-subtle bg-surface-card p-2 shadow-raised"
                    >
                        <span aria-hidden="true" class="absolute -top-1.5 right-[18px] size-3 rotate-45 rounded-tl-xs border-t border-l border-line-subtle bg-surface-card"></span>

                        <div class="flex flex-col gap-1 border-b border-line-subtle px-3 pt-2 pb-3">
                            <b class="truncate type-label-lg text-fg-strong">{{ $accountUser->name }}</b>
                            <span class="truncate type-body-sm text-fg-muted">{{ $accountUser->email }}</span>
                            @if ($accountMembership)
                                <x-pill tone="success" icon="crown" size="sm" class="mt-1 h-6! self-start rounded-sm! px-2! text-xs!">{{ $accountMembership->getLabel() }}</x-pill>
                            @endif
                        </div>

                        @foreach ($accountLinks as $link)
                            <a href="{{ $link['href'] }}" class="flex h-10 items-center gap-3 rounded-md px-3 type-label text-fg-body no-underline transition-interactive hover:bg-surface-hover hover:text-fg-body hover:no-underline">
                                <x-icon :name="$link['icon']" size="md" class="text-fg-muted" />
                                {{ $link['label'] }}
                            </a>
                        @endforeach

                        <form method="POST" action="{{ route('logout') }}" class="border-t border-line-subtle pt-1">
                            @csrf
                            <button type="submit" class="flex h-10 w-full cursor-pointer items-center gap-3 rounded-md px-3 type-label text-danger-text transition-interactive hover:bg-danger-bg">
                                <x-icon name="log-out" size="md" />
                                Đăng xuất
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <a
                    href="{{ route('login') }}"
                    aria-label="Đăng nhập"
                    class="flex size-12 items-center justify-center rounded-full border-2 border-primary-border bg-primary-bg text-primary-text no-underline transition-interactive hover:bg-primary-bg-hover hover:text-primary-text hover:no-underline"
                >
                    <x-icon name="user" size="lg" />
                </a>
            @endif
        </div>
    </div>

    {{-- Mobile / tablet --}}
    <div class="xl:hidden">
        <div class="flex h-16 items-center justify-between gap-3 px-4">
            <a href="{{ $homeHref }}" aria-label="Học Tiếng Anh — Trang chủ" class="flex flex-none items-center no-underline hover:no-underline"><x-logo :size="38" /></a>

            <div class="flex items-center gap-2">
                <x-icon-button
                    icon="search"
                    label="Tìm kiếm"
                    variant="ghost"
                    shape="circle"
                    size="sm"
                    aria-expanded="false"
                    aria-controls="nav-mobile-search"
                    data-toggle="nav-mobile-search"
                    data-toggle-group="nav-mobile"
                    data-toggle-icon="x"
                    class="aria-expanded:border-primary aria-expanded:bg-primary aria-expanded:text-on-primary"
                />
                <x-icon-button
                    icon="menu"
                    label="Menu"
                    variant="soft"
                    size="sm"
                    aria-expanded="false"
                    aria-controls="nav-mobile-menu"
                    data-toggle="nav-mobile-menu"
                    data-toggle-group="nav-mobile"
                    data-toggle-icon="x"
                    class="aria-expanded:border-primary aria-expanded:bg-primary aria-expanded:text-on-primary"
                />
            </div>
        </div>

        <div id="nav-mobile-search" hidden class="border-t border-line-subtle px-4 py-3">
            <form method="GET" action="{{ route('search') }}" role="search">
                <x-search-input name="q" :value="request('q')" placeholder="Tìm khóa học, từ vựng, flashcard..." shape="pill" class="w-full" />
            </form>
        </div>

        <nav id="nav-mobile-menu" hidden aria-label="Chính" class="flex flex-col gap-1 border-t border-line-subtle px-4 pt-2 pb-4">
            @foreach ($items as $item)
                @php $isActive = $item['id'] === $active; @endphp
                <a
                    href="{{ $item['href'] ?? '#' }}"
                    @if ($isActive) aria-current="page" @endif
                    @class([
                        'flex h-12 items-center gap-3 rounded-md px-4 type-label no-underline transition-interactive hover:no-underline',
                        'bg-primary-bg text-primary-text hover:text-primary-text' => $isActive,
                        'text-fg-body hover:bg-surface-hover hover:text-fg-body' => ! $isActive,
                    ])
                >
                    @if (! empty($item['icon']))
                        <x-icon :name="$item['icon']" size="md" />
                    @endif
                    {{ $item['label'] }}
                </a>
            @endforeach

            @auth
                <a href="{{ route('profile.show') }}" class="flex h-12 items-center gap-3 rounded-md px-4 type-label text-fg-body no-underline transition-interactive hover:bg-surface-hover hover:text-fg-body hover:no-underline">
                    <x-icon name="user" size="md" />
                    Hồ sơ của bạn
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex h-12 w-full cursor-pointer items-center gap-3 rounded-md px-4 type-label text-fg-body transition-interactive hover:bg-surface-hover">
                        <x-icon name="log-out" size="md" />
                        Đăng xuất
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="flex h-12 items-center gap-3 rounded-md px-4 type-label text-fg-body no-underline transition-interactive hover:bg-surface-hover hover:text-fg-body hover:no-underline">
                    <x-icon name="user" size="md" />
                    Đăng nhập
                </a>
            @endauth
        </nav>
    </div>
</header>
