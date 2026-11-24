<?php

use App\Enums\ClassShift;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the month to edit to an admin and the read-only week to everyone else', function () {
    $this->get('/schedule')->assertOk()->assertViewIs('schedule.week');

    $this->actingAs(User::factory()->create())->get('/schedule')->assertOk()->assertViewIs('schedule.week');

    $this->actingAs(User::factory()->admin()->create())->get('/schedule')->assertOk()->assertViewIs('admin.schedule.index');
});

it('shows the week of the latest classes, not the ones planned after this week', function () {
    $this->travelTo('2026-03-11');
    [$old, $recent, $planned] = Classroom::factory()->count(3)->sequence(['name' => 'Old Class'], ['name' => 'Recent Class'], ['name' => 'Planned Class'])->create();

    foreach ([['2026-02-10', $old], ['2026-03-04', $recent], ['2026-03-25', $planned]] as [$date, $classroom]) {
        ClassSession::factory()->create(['classroom_id' => $classroom->id, 'date' => $date, 'shift' => ClassShift::First, 'note' => 'Note of '.$classroom->name]);
    }

    $this->get('/schedule')
        ->assertOk()
        ->assertSee('02/03 - 08/03/2026')
        ->assertSee('Recent Class')
        ->assertDontSee('Old Class')
        ->assertDontSee('Planned Class')
        ->assertDontSee('Note of Recent Class')
        ->assertDontSee('data-schedule-slot', false);

    $this->actingAs(User::factory()->admin()->create())->get('/schedule?month=2026-03')->assertSee('Note of Recent Class');
});

it('shows the current week when there is no class yet', function () {
    $this->travelTo('2026-03-11');

    $this->get('/schedule')->assertOk()->assertSee('09/03 - 15/03/2026');
});

it('shows the classrooms scheduled in the requested month', function () {
    $classroom = Classroom::factory()->create(['name' => 'IELTS Sunrise']);
    ClassSession::factory()->create([
        'classroom_id' => $classroom->id,
        'date' => '2026-03-10',
        'shift' => ClassShift::Third,
    ]);

    $this->actingAs(User::factory()->admin()->create());

    $scheduledIn = fn (string $month) => $this->get('/schedule?month='.$month)->assertOk()->viewData('scheduledSessions')->get('2026-03-10|3');

    expect($scheduledIn('2026-03')->classroom->name)->toBe('IELTS Sunrise')
        ->and($scheduledIn('2026-05'))->toBeNull();
});

it('splits the month into Monday to Sunday weeks', function () {
    $this->actingAs(User::factory()->admin()->create());

    $weeks = $this->get('/schedule?month=2026-03')->viewData('weeks');

    expect($weeks)->toHaveCount(6)
        ->and($weeks[0][0]['date'])->toBe('2026-02-23')
        ->and($weeks[5][6]['date'])->toBe('2026-04-05');
});

it('falls back to the current month when the month is not valid', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get('/schedule?month=oops')->assertOk()->assertViewHas('month', fn ($month) => $month->format('Y-m') === now()->format('Y-m'));
});

it('sets the classroom of a slot, replacing the one it had', function () {
    [$first, $second] = Classroom::factory()->count(2)->create();
    ClassSession::factory()->create([
        'classroom_id' => $first->id,
        'date' => '2026-03-10',
        'shift' => ClassShift::First,
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->put('/schedule', ['month' => '2026-03', 'date' => '2026-03-10', 'shift' => 1, 'classroom_id' => $second->id, 'note' => 'Học bù'])
        ->assertRedirect(route('admin.schedule.index', ['month' => '2026-03']));

    expect(ClassSession::query()->pluck('note', 'classroom_id')->all())->toBe([$second->id => 'Học bù']);

    $this->put('/schedule', ['month' => '2026-03', 'date' => '2026-03-10', 'shift' => 1, 'classroom_id' => ''])->assertRedirect();

    expect(ClassSession::query()->count())->toBe(0);
});

it('does not let a regular user change the schedule', function () {
    $classroom = Classroom::factory()->create();

    $this->actingAs(User::factory()->create())
        ->put('/schedule', ['month' => '2026-03', 'date' => '2026-03-10', 'shift' => 1, 'classroom_id' => $classroom->id])
        ->assertForbidden();

    expect(ClassSession::query()->count())->toBe(0);
});

it('links the schedule in the footer', function () {
    $this->get(route('home'))->assertOk()->assertSee('Lịch học')->assertSee(route('admin.schedule.index'));
});

it('rejects a slot that is not in the month shown', function () {
    $classroom = Classroom::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put('/schedule', ['month' => '2026-03', 'date' => '2026-04-01', 'shift' => 1, 'classroom_id' => $classroom->id])
        ->assertSessionHasErrors('date');

    expect(ClassSession::query()->count())->toBe(0);
});

it('counts the shifts of each classroom in the days of the month only', function () {
    [$first, $second] = Classroom::factory()->count(2)->create();

    foreach (['2026-02-28' => $first, '2026-03-01' => $first, '2026-03-31' => $first, '2026-04-01' => $first, '2026-03-15' => $second] as $date => $classroom) {
        ClassSession::factory()->create(['classroom_id' => $classroom->id, 'date' => $date, 'shift' => ClassShift::First]);
    }

    $this->actingAs(User::factory()->admin()->create());

    $this->get('/schedule?month=2026-03')->assertOk()->assertViewHas('shiftCounts', [$first->id => 2, $second->id => 1]);
});

it('shows the hours of each shift', function () {
    $this->get('/schedule')->assertOk()->assertSeeInOrder(['Ca 1', '7h30 - 9h30', 'Ca 2', '9h30 - 11h30', 'Ca 3', '14h - 16h', 'Ca 4', '16h30 - 18h30', 'Ca 5', '19h30 - 21h30']);

    $this->actingAs(User::factory()->admin()->create())->get('/schedule')->assertSee('7h30 - 9h30')->assertSee('19h30 - 21h30');
});
