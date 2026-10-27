{{--
    Toast at the top right that cheers each achievement of a learner as it happens: a review part completed for the first time,
    a new personal best or a climb in the BXH (see LearnerAchieved). app.js listens on the public `achievements` channel and shows them one after
    the other, each for a few seconds. Confetti bursts out of the card as it comes in (`data-achievement-confetti`).
--}}
<div data-achievement-toasts {{ $attributes->class(['pointer-events-none fixed top-24 right-4 z-30 flex w-[min(360px,calc(100vw-2rem))] flex-col print:hidden sm:right-6']) }} aria-live="polite">
    <template data-achievement-template>
        <div role="status" class="achievement-toast pointer-events-auto relative isolate flex items-start gap-4 rounded-lg bg-surface-card p-4 shadow-raised">
            <span data-achievement-confetti aria-hidden="true" class="pointer-events-none absolute inset-0 z-10"></span>
            <span class="flex size-14 flex-none items-center justify-center rounded-full bg-accent text-on-accent">
                <x-icon name="trophy" size="xl" data-achievement-icon="record" />
                <x-icon name="badge-check" size="xl" data-achievement-icon="completed" />
                <x-icon name="trending-up" size="xl" data-achievement-icon="rank" />
            </span>
            <div class="flex min-w-0 flex-1 flex-col gap-1">
                <span data-achievement-title class="type-label-sm font-semibold text-accent-text"></span>
                <p class="type-body text-fg-body"><strong data-achievement-name class="font-semibold text-fg-strong"></strong> <span data-achievement-message></span></p>
                <span class="flex min-w-0 items-center gap-2">
                    <span data-achievement-value class="flex-none rounded-sm bg-accent-bg px-2 type-label font-bold text-fg-score"></span>
                    <span data-achievement-subject class="truncate type-body-sm text-fg-muted"></span>
                </span>
            </div>
            <x-icon-button icon="x" label="Đóng" variant="ghost" size="sm" data-achievement-close />
        </div>
    </template>
</div>
