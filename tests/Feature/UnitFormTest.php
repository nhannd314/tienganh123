<?php

use App\Filament\Resources\Units\Pages\CreateUnit;
use App\Filament\Resources\Units\Pages\EditUnit;
use App\Models\Course;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('generates the slug from the title when the slug is left empty', function () {
    $course = Course::factory()->create();

    Livewire::test(CreateUnit::class)
        ->fillForm(['course_id' => $course->id, 'title' => 'My family', 'sort_order' => 1])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Unit::firstWhere('title', 'My family'))
        ->slug->toBe('my-family')
        ->is_published->toBeFalse();
});

it('rejects a slug already used within the same course', function () {
    $course = Course::factory()->create();
    Unit::factory()->for($course)->create(['slug' => 'hello']);

    Livewire::test(CreateUnit::class)
        ->fillForm(['course_id' => $course->id, 'title' => 'Hello', 'slug' => 'hello'])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'unique']);
});

it('allows the same slug in a different course', function () {
    Unit::factory()->for(Course::factory())->create(['slug' => 'hello']);
    $otherCourse = Course::factory()->create();

    Livewire::test(CreateUnit::class)
        ->fillForm(['course_id' => $otherCourse->id, 'title' => 'Hello', 'slug' => 'hello'])
        ->call('create')
        ->assertHasNoFormErrors();
});

it('allows keeping the own slug when editing a unit', function () {
    $unit = Unit::factory()->create(['slug' => 'hello']);

    Livewire::test(EditUnit::class, ['record' => $unit->getRouteKey()])
        ->fillForm(['title' => 'Hello again'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($unit->refresh()->title)->toBe('Hello again');
});
