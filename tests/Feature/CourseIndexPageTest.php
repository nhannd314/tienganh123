<?php

use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists published courses by sort order and hides drafts', function () {
    Course::factory()->published()->create(['title' => 'Second Course', 'sort_order' => 2]);
    Course::factory()->published()->create(['title' => 'First Course', 'sort_order' => 1]);
    Course::factory()->create(['title' => 'Draft Course']);

    $this->get(route('courses.index'))
        ->assertOk()
        ->assertSeeInOrder(['Tất cả khóa học', 'First Course', 'Second Course'])
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

it('labels each course card with its price, Miễn phí for a free course', function () {
    Course::factory()->published()->create(['title' => 'Khóa mở']);
    Course::factory()->published()->priced(50000)->create(['title' => 'Khóa có phí']);

    $this->get(route('courses.index'))
        ->assertSeeInOrder(['Khóa mở', 'Miễn phí', 'Khóa có phí', '50.000₫'])
        ->assertDontSee('/tháng')
        ->assertSee('icon-gift', false)
        ->assertSee('icon-tag', false);
});

it('labels the course cards of the home page with their price', function () {
    Course::factory()->published()->priced(80000)->create();

    $this->get(route('home'))->assertSee('80.000₫')->assertSee('icon-tag', false);
});

it('shows a paid course card on sale off a struck-through original price, and a free one without', function () {
    Course::factory()->published()->priced(100000)->onSale(200000)->create(['sort_order' => 1]);
    Course::factory()->published()->create(['monthly_price' => 0, 'sort_order' => 2]);

    $response = $this->get(route('courses.index'))
        ->assertSeeInOrder(['Sale 50%', '100.000₫', '<del class="type-body-sm text-fg-muted">200.000₫</del>', 'Miễn phí'], false);

    expect(substr_count($response->getContent(), 'Sale 50%'))->toBe(1);
});
