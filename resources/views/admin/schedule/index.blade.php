@php
    $dayNames = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ nhật'];
    $periods = collect($shifts)->groupBy(fn (\App\Enums\ClassShift $shift): string => $shift->getPeriodLabel());
@endphp

<x-layouts.app title="Lịch học" noindex main-class="flex flex-col gap-6 pt-6 pb-10">
    <x-slot:header>
        <x-site-header />
    </x-slot:header>

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="type-h1 text-fg-strong">Lịch học · Tháng {{ $month->format('m/Y') }}</h1>

            <div class="flex items-center gap-2">
                <x-button :href="route('admin.schedule.index', ['month' => $previousMonth])" variant="outline" size="sm">‹ Tháng trước</x-button>
                <x-button :href="route('admin.schedule.index', ['month' => $nextMonth])" variant="outline" size="sm">Tháng sau ›</x-button>
            </div>
        </div>

        @if (session('status'))
            <x-alert tone="success">{{ session('status') }}</x-alert>
        @endif

        @error('date')
            <x-alert tone="danger">{{ $message }}</x-alert>
        @enderror

        <div class="flex flex-col gap-8 rounded-lg bg-surface-card p-6 shadow-card">
        @foreach ($weeks as $weekIndex => $week)
            @php($isCurrentWeek = collect($week)->contains('isToday', true))
            <section
                class="flex scroll-mt-4 flex-col gap-3"
                @if (! session('status') && $isCurrentWeek) data-schedule-current-week @endif
            >
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[40rem] table-fixed border-collapse">
                        <colgroup>
                            <col class="w-20">
                            <col class="w-32">
                            <col span="7">
                        </colgroup>
                        <thead>
                            <tr>
                                <th colspan="2" @class([
                                    'border border-line-strong p-2 type-label',
                                    'bg-ink-50 text-fg-strong' => ! $isCurrentWeek,
                                    'bg-primary-bg text-primary-text' => $isCurrentWeek,
                                ])>Tuần {{ $weekIndex + 1 }}</th>
                                @foreach ($week as $dayIndex => $day)
                                    <th @class([
                                        'border border-line-strong p-2 type-label',
                                        'bg-ink-50 text-fg-strong' => ! $day['isToday'],
                                        'bg-primary-bg text-primary-text' => $day['isToday'],
                                    ])>
                                        {{ $dayNames[$dayIndex] }}
                                        <div class="type-body-sm font-normal">{{ \Illuminate\Support\Carbon::parse($day['date'])->format('d/m') }}</div>
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
                                        @foreach ($week as $day)
                                            @php($session = $scheduledSessions->get($day['date'].'|'.$shift->value))
                                            <td class="h-14 border border-line-strong p-0 align-top">
                                                <button
                                                    type="button"
                                                    @disabled(! $day['inMonth'])
                                                    data-schedule-slot
                                                    data-date="{{ $day['date'] }}"
                                                    data-shift="{{ $shift->value }}"
                                                    data-title="{{ $shift->getLabel() }} · {{ \Illuminate\Support\Carbon::parse($day['date'])->format('d/m/Y') }}"
                                                    data-classroom="{{ $session?->classroom_id }}"
                                                    data-note="{{ $session?->note }}"
                                                    @class([
                                                        'flex h-full min-h-14 w-full flex-col gap-1 p-1 text-left transition-interactive',
                                                        'cursor-pointer bg-white hover:bg-ink-50' => $day['inMonth'],
                                                        'cursor-not-allowed bg-ink-100' => ! $day['inMonth'],
                                                    ])
                                                    @if (! $day['inMonth']) style="background-image: repeating-linear-gradient(135deg, transparent 0 6px, rgb(90 95 125 / 0.14) 6px 12px);" @endif
                                                    aria-label="Chọn lớp học cho {{ $shift->getLabel() }} ngày {{ \Illuminate\Support\Carbon::parse($day['date'])->format('d/m/Y') }}"
                                                >
                                                    @if ($session)
                                                        <span class="block rounded-sm border px-1.5 py-0.5 type-body-sm font-medium" style="{{ $session->classroom->badgeStyle() }}" @if (filled($session->note)) title="{{ $session->note }}" @endif>
                                                            <span class="block truncate">{{ $session->classroom->name }}</span>
                                                            @if (filled($session->note))
                                                                <span class="block truncate text-[0.7rem] font-normal opacity-80">{{ $session->note }}</span>
                                                            @endif
                                                        </span>
                                                    @endif
                                                </button>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach

            <section class="flex flex-col gap-3">
                <h2 class="type-h3 text-fg-strong">Thống kê tháng {{ $month->format('m/Y') }}</h2>

                <table class="w-full max-w-md border-collapse">
                    <thead>
                        <tr>
                            <th class="border border-line-strong bg-ink-50 px-3 py-2 text-left type-label text-fg-strong">Lớp học</th>
                            <th class="w-28 border border-line-strong bg-ink-50 px-3 py-2 text-right type-label text-fg-strong">Số ca</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($classrooms as $classroom)
                            <tr>
                                <td class="border border-line-strong bg-white px-3 py-2">
                                    <span class="inline-block max-w-full truncate rounded-sm border px-2 py-0.5 type-body-sm font-medium" style="{{ $classroom->badgeStyle() }}">{{ $classroom->name }}</span>
                                </td>
                                <td class="border border-line-strong bg-white px-3 py-2 text-right type-body text-fg-body">{{ $shiftCounts[$classroom->id] ?? 0 }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="border border-line-strong bg-white px-3 py-2 type-body text-fg-muted">Chưa có lớp học nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th class="border border-line-strong bg-ink-50 px-3 py-2 text-left type-label text-fg-strong">Tổng</th>
                            <th class="border border-line-strong bg-ink-50 px-3 py-2 text-right type-label text-fg-strong">{{ array_sum($shiftCounts) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </section>
        </div>
    </div>

    <dialog data-schedule-dialog class="m-auto w-full max-w-md rounded-lg bg-surface-card p-0 shadow-card backdrop:bg-ink-900/50">
        <form method="POST" action="{{ route('admin.schedule.update') }}" class="flex flex-col gap-4 p-6">
            @csrf
            @method('PUT')

            <input type="hidden" name="date" data-schedule-date>
            <input type="hidden" name="shift" data-schedule-shift>
            <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">

            <h2 class="type-h3 text-fg-strong" data-schedule-dialog-title></h2>

            <label class="grid gap-2">
                <span class="type-label text-fg-body">Lớp học</span>
                <select name="classroom_id" data-schedule-classroom class="type-body rounded-sm border border-line-strong bg-surface-card px-3 py-2 text-fg-body">
                    <option value="">— Không có lớp —</option>
                    @foreach ($classrooms as $classroom)
                        <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                    @endforeach
                </select>
            </label>

            <label class="grid gap-2">
                <span class="type-label text-fg-body">Ghi chú</span>
                <input
                    type="text"
                    name="note"
                    maxlength="500"
                    data-schedule-note
                    class="type-body rounded-sm border border-line-strong bg-surface-card px-3 py-2 text-fg-body disabled:bg-surface-disabled disabled:opacity-50"
                >
            </label>

            <div class="flex justify-end gap-3">
                <x-button type="button" variant="outline" data-schedule-cancel>Hủy</x-button>
                <x-button type="submit">Lưu</x-button>
            </div>
        </form>
    </dialog>

    @vite('resources/js/class-schedule.js')
</x-layouts.app>
