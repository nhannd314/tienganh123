<?php

use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists published courses by sort order, then the newest first, and hides drafts', function () {
    Course::factory()->published()->create(['title' => 'Last Course', 'sort_order' => 2, 'created_at' => now()->addDay()]);
    Course::factory()->published()->create(['title' => 'Older Course', 'sort_order' => 1, 'created_at' => now()->subDay()]);
    Course::factory()->published()->create(['title' => 'Newer Course', 'sort_order' => 1, 'created_at' => now()]);
    Course::factory()->create(['title' => 'Draft Course']);

    $this->get(route('courses.index'))
        ->assertOk()
        ->assertSeeInOrder(['Tất cả khóa học', 'Newer Course', 'Older Course', 'Last Course'])
        ->assertDontSee('Draft Course');
});

it('paginates the courses', function () {
    Course::factory()->published()->count(13)->sequence(fn ($sequence) => [
        'title' => 'Course '.str_pad($sequence->index + 1, 2, '0', STR_PAD_LEFT),
        'sort_order' => $sequence->index + 1,
    ])->create();

    $this->get(route('courses.index'))
        ->assertSee('Course 12')
        ->assertDontSee('Course 13')
        ->assertSee('aria-label="Phân trang"', false);

    $this->get(route('courses.index', ['page' => 2]))
        ->assertSee('Course 13')
        ->assertDontSee('Course 01');
});

it('hides the pager when everything fits on one page', function () {
    Course::factory()->published()->count(3)->create();

    $this->get(route('courses.index'))->assertDontSee('aria-label="Phân trang"', false);
});

it('shows an empty state when there are no published courses', function () {
    Course::factory()->create();

    $this->get(route('courses.index'))->assertSee('Chưa có khóa học nào');
});

it('links each course to its detail page', function () {
    $course = Course::factory()->published()->create();

    $this->get(route('courses.index'))->assertSee(route('courses.show', $course), false);
});

it('tags each course card with its level then its age groups instead of its price', function () {
    Course::factory()->published()->priced(100000)->onSale(200000)->create([
        'title' => 'Khóa thiếu nhi',
        'age_groups' => ['Mầm non', 'Tiểu học'],
        'level' => 'Sơ cấp',
    ]);

    $this->get(route('courses.index'))
        ->assertSeeInOrder(['Khóa thiếu nhi', 'Sơ cấp', 'Mầm non', 'Tiểu học'])
        ->assertDontSee('100.000₫')
        ->assertDontSee('Sale 50%');
});

it('shows a course card without tags when it has no age group or level', function () {
    Course::factory()->published()->create(['age_groups' => null, 'level' => null]);

    $this->get(route('courses.index'))->assertDontSee('rounded-sm! px-2! text-xs!', false);
});

it('tags the course cards of the home page', function () {
    Course::factory()->published()->create(['age_groups' => ['THCS'], 'level' => 'Trung cấp']);

    $this->get(route('home'))->assertSeeInOrder(['Trung cấp', 'THCS']);
});
