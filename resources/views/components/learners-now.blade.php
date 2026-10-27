{{--
    Floating chat button, bottom right, with the number of users learning right now; it opens their
    list, each with what they are doing. The list is live: app.js joins the `learning` presence channel from every page and
    fills it from its members (see ShareLearningActivity). A guest cannot join, so is asked to sign in.
--}}
@php
    $avatarStyles = [
        'bg-primary-bg-hover text-primary-text',
        'bg-secondary-bg-hover text-secondary-text',
        'bg-success-bg-hover text-success-text',
        'bg-accent-bg-hover text-accent-text',
    ];
@endphp

<div {{ $attributes->class(['print:hidden']) }} @auth data-learners-now data-avatar-styles='@json($avatarStyles)' @endauth>
    <button
        type="button"
        aria-expanded="false"
        aria-controls="learners-now"
        aria-label="Đang học"
        title="Đang học"
        data-toggle="learners-now"
        class="fixed right-4 bottom-4 z-30 flex size-14 cursor-pointer items-center justify-center rounded-full bg-primary text-white shadow-raised transition-interactive hover:bg-primary-strong active:scale-95 sm:right-6 sm:bottom-6"
    >
        <svg viewBox="0 0 32 32" aria-hidden="true" class="size-7">
            <path fill="currentColor" d="M16 3C8.82 3 3 8.2 3 14.6c0 3.5 1.74 6.63 4.47 8.75l-.9 4.4a.8.8 0 0 0 1.14.88l4.68-2.4c1.14.29 2.35.45 3.61.45 7.18 0 13-5.2 13-11.6S23.18 3 16 3Z" />
            <circle cx="10.5" cy="14.6" r="1.9" class="fill-primary" />
            <circle cx="16" cy="14.6" r="1.9" class="fill-primary" />
            <circle cx="21.5" cy="14.6" r="1.9" class="fill-primary" />
        </svg>

        <span data-learners-badge hidden class="absolute -top-1 -right-1 flex h-6 min-w-6 items-center justify-center rounded-full border-2 border-white bg-success px-1.5 type-label-sm font-medium text-white"></span>
    </button>

    <section
        id="learners-now"
        role="dialog"
        aria-label="Đang học bài"
        data-toggle-outside
        hidden
        class="fixed right-4 bottom-22 z-40 flex max-h-[min(480px,calc(100dvh-8rem))] w-[min(320px,calc(100vw-2rem))] flex-col overflow-hidden rounded-lg bg-surface-card shadow-raised sm:right-6 sm:bottom-24"
    >
        <div class="flex items-center gap-3 border-b border-line-default py-3 pr-3 pl-5">
            <span class="size-2.5 flex-none rounded-full bg-success"></span>
            <h2 class="type-label-lg font-semibold text-fg-strong">Đang học bài</h2>
            @auth
                <x-pill tone="success" size="sm" class="h-7"><span data-learners-count>0</span> bạn</x-pill>
            @endauth
            <x-icon-button icon="x" label="Đóng" variant="ghost" size="sm" data-toggle-close="learners-now" class="ml-auto" />
        </div>

        @auth
            <p data-learners-empty class="px-5 py-6 text-center type-body-sm text-fg-muted">Chưa có bạn nào đang học. Bắt đầu học ngay nhé!</p>
            <ul data-learners-list hidden class="flex flex-col overflow-y-auto p-2"></ul>

            <template data-learner-template>
                <li class="flex items-center gap-3 rounded-md px-2 py-2 hover:bg-surface-hover">
                    <span data-learner-avatar class="relative flex size-10 flex-none items-center justify-center rounded-full type-label-sm">
                        <img data-learner-image alt="" class="size-full rounded-full object-cover">
                        <span data-learner-initials></span>
                        <span class="absolute -right-0.5 -bottom-0.5 size-3 rounded-full border-2 border-surface-card bg-success"></span>
                    </span>
                    <span class="flex min-w-0 flex-col">
                        <span data-learner-name class="truncate type-label font-semibold text-fg-strong"></span>
                        <span data-learner-activity class="truncate type-body-sm text-fg-muted"></span>
                    </span>
                </li>
            </template>
        @else
            <div class="flex flex-col items-center gap-3 px-5 py-6 text-center">
                <p class="type-body-sm text-fg-muted">Đăng nhập để xem các bạn đang học cùng bạn nhé!</p>
                <x-button :href="route('login')" size="sm" icon-left="log-in">Đăng nhập</x-button>
            </div>
        @endauth
    </section>
</div>
