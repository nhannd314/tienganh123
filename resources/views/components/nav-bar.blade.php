{{--
    Site header: brand mark, main navigation, search, notifications and account.
    `items` is a list of ['id' => ..., 'label' => ..., 'icon' => ..., 'href' => ...]; `active` is the current item id.
    Below xl (1280px) it collapses into a sticky bar with search and menu toggles (see resources/js/app.js).
--}}
@props([
    'items' => [],
    'active' => null,
    'homeHref' => '/',
    'logoSrc' => null,
    'avatarSrc' => null,
    'notifications' => 0,
])

<header {{ $attributes->class(['sticky top-0 z-20 border-b border-line-subtle bg-surface-header shadow-xs xl:static']) }}>
    {{-- Desktop --}}
    <div class="mx-auto hidden h-20 max-w-layout items-center gap-10 px-10 xl:flex">
        <a href="{{ $homeHref }}" aria-label="FunEnglish — Trang chủ" class="flex flex-none items-center no-underline hover:no-underline">
            @if ($logoSrc)
                <img src="{{ $logoSrc }}" alt="" class="h-14">
            @else
                <b class="text-3xl leading-none font-bold tracking-tight text-primary">FunEnglish</b>
            @endif
        </a>

        <nav aria-label="Chính" class="flex min-w-0 flex-1 gap-1">
            @foreach ($items as $item)
                @php($isActive = $item['id'] === $active)
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
            <x-search-input name="q" shape="pill" />

            <x-badge :count="$notifications" inset>
                <x-icon-button
                    icon="bell"
                    :label="'Thông báo'.($notifications ? ' ('.$notifications.' mới)' : '')"
                    variant="ghost"
                    shape="circle"
                />
            </x-badge>

            <span aria-hidden="true" class="h-8 w-px bg-line-default"></span>

            @if ($avatarSrc)
                <button type="button" aria-label="Tài khoản" class="flex cursor-pointer rounded-full">
                    <img src="{{ $avatarSrc }}" alt="" class="size-12 rounded-full border-2 border-primary-border object-cover">
                </button>
            @elseif (auth()->check())
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-icon-button icon="log-out" label="Đăng xuất" variant="soft" shape="circle" type="submit" />
                </form>
            @else
                <x-icon-button icon="user" label="Đăng nhập" variant="soft" shape="circle" :href="route('login')" />
            @endif
        </div>
    </div>

    {{-- Mobile / tablet --}}
    <div class="xl:hidden">
        <div class="flex h-16 items-center justify-between gap-3 px-4">
            <a href="{{ $homeHref }}" aria-label="FunEnglish — Trang chủ" class="text-2xl leading-none font-bold tracking-tight text-primary no-underline hover:text-primary hover:no-underline">FunEnglish</a>

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
            <x-search-input name="q" placeholder="Tìm khóa học, từ vựng..." shape="pill" class="w-full" />
        </div>

        <nav id="nav-mobile-menu" hidden aria-label="Chính" class="flex flex-col gap-1 border-t border-line-subtle px-4 pt-2 pb-4">
            @foreach ($items as $item)
                @php($isActive = $item['id'] === $active)
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
