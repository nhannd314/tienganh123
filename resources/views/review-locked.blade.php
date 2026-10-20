{{-- Members-only review (of a vocabulary or a sentence set) opened by a user without a running membership: why it is locked, and a way back. --}}
<x-layouts.app :title="$title" main-class="flex flex-col gap-6 pt-6 pb-10">
    <x-slot:header>
        <x-site-header :active="$headerActive" />
    </x-slot:header>

    <x-breadcrumbs :items="$breadcrumbs" />

    <section class="mx-auto flex w-full max-w-160 flex-col items-center gap-6 rounded-lg bg-surface-card p-8 text-center shadow-card">
        <span class="flex size-16 items-center justify-center rounded-full bg-primary-bg text-primary">
            <x-icon name="lock" size="xl" />
        </span>

        <div class="flex flex-col gap-2">
            <h1 class="type-h2 text-fg-strong">{{ $heading }}</h1>
            <p class="type-body text-pretty text-fg-body">{{ $message }}</p>
        </div>

        <x-button size="lg" icon-left="book-open" :href="$backUrl">{{ $backLabel }}</x-button>
    </section>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
