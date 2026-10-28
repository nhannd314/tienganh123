{{-- The BXH button, fixed on the right side of every page, and the panel it opens with the top 10 learners (see App\View\Components\Leaderboard). --}}
@php
    $rankTitles = [
        ['title' => 'Nhà vua', 'color' => 'text-accent-text'],
        ['title' => 'Thái sư', 'color' => 'text-primary-text'],
        ['title' => 'Đô đốc', 'color' => 'text-secondary-text'],
        ['title' => 'Đại tướng', 'color' => 'text-success-text'],
        ['title' => 'Phó tướng', 'color' => 'text-fg-strong'],
        ['title' => 'Hiệp sĩ', 'color' => 'text-fg-strong'],
        ['title' => 'Cung thủ', 'color' => 'text-fg-strong'],
        ['title' => 'Kỵ binh', 'color' => 'text-fg-strong'],
        ['title' => 'Lính tiên phong', 'color' => 'text-fg-strong'],
        ['title' => 'Tân binh', 'color' => 'text-fg-strong'],
    ];
    $podiumStyles = [
        ['row' => 'border-accent-border bg-accent-bg', 'rank' => 'bg-accent text-on-accent'],
        ['row' => 'border-primary-border bg-primary-bg', 'rank' => 'bg-primary text-on-primary'],
        ['row' => 'border-secondary-border bg-secondary-bg', 'rank' => 'bg-secondary text-on-secondary'],
    ];
    $avatarStyles = [
        'bg-primary-bg-hover text-primary-text',
        'bg-secondary-bg-hover text-secondary-text',
        'bg-success-bg-hover text-success-text',
        'bg-accent-bg-hover text-accent-text',
    ];
@endphp

<div {{ $attributes->class(['print:hidden']) }}>
    <button
        type="button"
        aria-expanded="false"
        aria-controls="leaderboard"
        aria-label="BXH"
        title="BXH"
        data-toggle="leaderboard"
        class="fixed top-1/3 right-0 z-30 flex -translate-y-1/2 cursor-pointer items-center justify-center rounded-l-md bg-accent px-3 py-3 text-on-accent shadow-[inset_4px_0_0_var(--color-accent-strong)] transition-interactive hover:bg-accent-strong"
    >
        <x-icon name="trophy" size="lg" />
    </button>

    <div id="leaderboard" hidden>
        <div data-toggle-close="leaderboard" class="fixed inset-0 z-40 bg-ink-900/32"></div>

        <aside role="dialog" aria-label="BXH" class="fixed inset-y-0 right-0 z-50 flex w-full max-w-100 flex-col gap-4 overflow-y-auto bg-surface-card p-6 shadow-raised">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="flex size-12 items-center justify-center rounded-full border border-accent-border bg-accent-bg">
                        <x-icon name="trophy" size="lg" class="text-accent-text" />
                    </div>
                    <div class="flex flex-col">
                        <h2 class="type-h2 text-fg-strong">BXH</h2>
                        <span class="type-body-sm text-fg-muted">Top 10 học sinh điểm cao nhất</span>
                    </div>
                </div>
                <x-icon-button icon="x" label="Đóng" variant="ghost" size="sm" data-toggle-close="leaderboard" />
            </div>

            @if ($leaderboard === [])
                <p class="type-body text-fg-muted">Chưa có học sinh nào ghi điểm.</p>
            @endif

            <ol class="flex flex-col gap-1">
                @foreach ($leaderboard as $index => $learner)
                    @php
                        $nameParts = explode(' ', $learner['name']);
                        $initials = mb_strtoupper(mb_substr($nameParts[count($nameParts) - 2] ?? '', 0, 1).mb_substr(end($nameParts), 0, 1));
                        $rankTitle = $rankTitles[$index] ?? ['title' => 'Tân binh', 'color' => 'text-fg-strong'];
                        $podium = $podiumStyles[$index] ?? ['row' => 'border-transparent', 'rank' => 'bg-surface-hover text-fg-body'];
                    @endphp
                    <li @class(['grid grid-cols-[36px_40px_minmax(0,1fr)_auto] items-center gap-3 rounded-md border px-3 py-2', $podium['row']])>
                        <span @class(['flex size-8 items-center justify-center rounded-full type-label font-bold', $podium['rank']])>{{ $index + 1 }}</span>
                        <span @class(['flex size-10 items-center justify-center overflow-hidden rounded-full type-label-sm', $avatarStyles[$index % count($avatarStyles)]])>
                            @if ($learner['avatarUrl'])
                                <img src="{{ $learner['avatarUrl'] }}" alt="" class="size-full object-cover">
                            @else
                                {{ $initials }}
                            @endif
                        </span>
                        <span class="flex min-w-0 flex-col">
                            <span @class(['type-label font-semibold', $rankTitle['color']])>{{ $rankTitle['title'] }}</span>
                            <span class="truncate type-body-sm text-fg-muted">{{ $learner['name'] }}</span>
                        </span>
                        <span class="flex items-center gap-1 type-label font-semibold text-fg-score">
                            <x-icon name="star" size="sm" class="text-accent-strong" />
                            {{ number_format($learner['points'], 0, ',', '.') }}
                        </span>
                    </li>
                @endforeach
            </ol>
        </aside>
    </div>
</div>
