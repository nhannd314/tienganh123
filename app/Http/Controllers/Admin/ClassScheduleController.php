<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClassShift;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateClassScheduleSlotRequest;
use App\Models\Classroom;
use App\Models\ClassSession;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The class timetable (/schedule). An admin sees a month: one table per week (Monday to Sunday), a row per shift, and the classrooms that study
 * in each slot (one class per slot), with its note. Clicking a slot opens a dialog to choose the classroom and write the note (see
 * resources/js/class-schedule.js). Anyone else sees one read-only table, the week of the latest classes (see latestWeek).
 */
class ClassScheduleController extends Controller
{
    public function index(Request $request): View
    {
        if (! $request->user()?->isAdmin()) {
            return $this->week();
        }

        $month = $this->resolveMonth($request->query('month'));
        $first = $month->startOfWeek(CarbonImmutable::MONDAY);
        $last = $month->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY);

        $scheduledSessions = ClassSession::query()
            ->with('classroom')
            ->whereDate('date', '>=', $first->toDateString())
            ->whereDate('date', '<=', $last->toDateString())
            ->get()
            ->keyBy(fn (ClassSession $session): string => $session->date->toDateString().'|'.$session->shift->value);

        return view('admin.schedule.index', [
            'month' => $month,
            'previousMonth' => $month->subMonth()->format('Y-m'),
            'nextMonth' => $month->addMonth()->format('Y-m'),
            'weeks' => $this->weeksOf($month),
            'scheduledSessions' => $scheduledSessions,
            'classrooms' => Classroom::query()->orderBy('name')->get(['id', 'name']),
            'shiftCounts' => $this->shiftCountsOf($month),
            'shifts' => ClassShift::cases(),
        ]);
    }

    /**
     * The read-only timetable of one week for the visitors who are not admins.
     */
    private function week(): View
    {
        $first = $this->latestWeek();
        $last = $first->endOfWeek(CarbonImmutable::SUNDAY);

        return view('schedule.week', [
            'first' => $first,
            'last' => $last,
            'days' => collect(range(0, 6))->map(fn (int $offset): CarbonImmutable => $first->addDays($offset)),
            'scheduledSessions' => ClassSession::query()
                ->with('classroom')
                ->whereDate('date', '>=', $first->toDateString())
                ->whereDate('date', '<=', $last->toDateString())
                ->get()
                ->keyBy(fn (ClassSession $session): string => $session->date->toDateString().'|'.$session->shift->value),
            'shifts' => ClassShift::cases(),
        ]);
    }

    /**
     * The Monday of the week of the latest class up to the end of this week, so classes planned ahead do not hide the week in
     * progress; the current week when there is no class yet.
     */
    private function latestWeek(): CarbonImmutable
    {
        $latest = ClassSession::query()
            ->whereDate('date', '<=', CarbonImmutable::now()->endOfWeek(CarbonImmutable::SUNDAY)->toDateString())
            ->max('date');

        return ($latest ? CarbonImmutable::parse($latest) : CarbonImmutable::now())->startOfWeek(CarbonImmutable::MONDAY)->startOfDay();
    }

    /**
     * Set the classroom of one slot (a slot holds one class at most) with its note; no classroom empties the slot.
     */
    public function update(UpdateClassScheduleSlotRequest $request): RedirectResponse
    {
        $date = $request->validated('date');
        $shift = (int) $request->validated('shift');
        $classroomId = $request->validated('classroom_id');
        $note = $request->validated('note');

        $slot = ClassSession::query()->whereDate('date', $date)->where('shift', $shift);

        if (blank($classroomId)) {
            $slot->delete();
        } else {
            $session = $slot->first() ?? new ClassSession(['date' => $date, 'shift' => $shift]);

            $session->fill(['classroom_id' => (int) $classroomId, 'note' => filled($note) ? trim($note) : null])->save();
        }

        return redirect()->route('admin.schedule.index', ['month' => substr($date, 0, 7)])->with('status', 'Đã lưu lịch học.');
    }

    /**
     * How many shifts each classroom has in the days of the month itself (not of the neighbouring months' days shown in its weeks).
     *
     * @return array<int, int> classroom id => shifts
     */
    private function shiftCountsOf(CarbonImmutable $month): array
    {
        return ClassSession::query()
            ->whereDate('date', '>=', $month->toDateString())
            ->whereDate('date', '<=', $month->endOfMonth()->toDateString())
            ->selectRaw('classroom_id, count(*) as shifts')
            ->groupBy('classroom_id')
            ->pluck('shifts', 'classroom_id')
            ->map(fn (mixed $shifts): int => (int) $shifts)
            ->all();
    }

    /**
     * The month asked for as Y-m, else the current one.
     */
    private function resolveMonth(mixed $value): CarbonImmutable
    {
        if (is_string($value) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value) === 1) {
            return CarbonImmutable::createFromFormat('Y-m-d', $value.'-01')->startOfDay();
        }

        return CarbonImmutable::now()->startOfMonth();
    }

    /**
     * The weeks (Monday to Sunday) that overlap the month, each as its seven days.
     *
     * @return list<list<array{date: string, inMonth: bool, isToday: bool}>>
     */
    private function weeksOf(CarbonImmutable $month): array
    {
        $cursor = $month->startOfWeek(CarbonImmutable::MONDAY);
        $last = $month->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY);
        $weeks = [];

        while ($cursor <= $last) {
            $week = [];

            for ($i = 0; $i < 7; $i++) {
                $week[] = [
                    'date' => $cursor->toDateString(),
                    'inMonth' => $cursor->month === $month->month,
                    'isToday' => $cursor->isToday(),
                ];
                $cursor = $cursor->addDay();
            }

            $weeks[] = $week;
        }

        return $weeks;
    }
}
