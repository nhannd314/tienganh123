@php
    $dayNames = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ nhật'];
    $periods = collect($shifts)->groupBy(fn (\App\Enums\ClassShift $shift): string => $shift->getPeriodLabel());
@endphp

<x-layouts.app title="Lịch học" main-class="flex flex-col gap-6 pt-6 pb-10">
    <x-slot:header>
        <x-site-header />
    </x-slot:header>

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6">
        <h1 class="type-h1 text-fg-strong">Lịch học · {{ $first->format('d/m') }} - {{ $last->format('d/m/Y') }}</h1>

        <section class="rounded-lg bg-surface-card p-6 shadow-card">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[40rem] table-fixed border-collapse">
                    <colgroup>
                        <col class="w-20">
                        <col class="w-32">
                        <col span="7">
                    </colgroup>
                    <thead>
                        <tr>
                            <th colspan="2" class="border border-line-strong bg-ink-50 p-2"></th>
                            @foreach ($days as $dayIndex => $day)
                                <th @class([
                                    'border border-line-strong p-2 type-label',
                                    'bg-ink-50 text-fg-strong' => ! $day->isToday(),
                                    'bg-primary-bg text-primary-text' => $day->isToday(),
                                ])>
                                    {{ $dayNames[$dayIndex] }}
                                    <div class="type-body-sm font-normal">{{ $day->format('d/m') }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($periods as $periodLabel => $periodShifts)
                            @foreach ($periodShifts as $shift)
                                <tr>
                                    @if ($loop->first)
                                        <th rowspan="{{ $periodShifts->count() }}" class="border border-line-strong bg-ink-50 px-3 py-4 type-label text-fg-strong">{{ $periodLabel }}</th>
                                    @endif
                                    <th class="border border-line-strong bg-ink-50 px-2 py-4 type-body-sm font-medium whitespace-nowrap text-fg-body">
                                        {{ $shift->getLabel() }}
                                        <div class="text-[0.7rem] font-normal text-fg-muted">{{ $shift->getTimeRange() }}</div>
                                    </th>
                                    @foreach ($days as $day)
                                        @php($session = $scheduledSessions->get($day->toDateString().'|'.$shift->value))
                                        <td class="h-14 border border-line-strong bg-white p-1 align-top">
                                            @if ($session)
                                                <span class="block truncate rounded-sm border px-1.5 py-0.5 type-body-sm font-medium" style="{{ $session->classroom->badgeStyle() }}">{{ $session->classroom->name }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <x-slot:footer>
        <x-site-footer />
    </x-slot:footer>
</x-layouts.app>
