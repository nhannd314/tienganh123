<?php

use App\Filament\Resources\Courses\Pages\CreateCourse;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\Pages\ListCourses;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('generates the slug from the title when the slug is left empty', function () {
    Livewire::test(CreateCourse::class)
        ->fillForm(['title' => 'Tiếng Anh lớp 3', 'sort_order' => 1])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Course::firstWhere('title', 'Tiếng Anh lớp 3'))
        ->slug->toBe('tieng-anh-lop-3')
        ->is_published->toBeFalse();
});

it('rejects a slug already used by another course', function () {
    Course::factory()->create(['slug' => 'lop-3']);

    Livewire::test(CreateCourse::class)
        ->fillForm(['title' => 'Lớp 3', 'slug' => 'lop-3'])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'unique']);
});

it('allows keeping the own slug when editing a course', function () {
    $course = Course::factory()->create(['slug' => 'lop-3']);

    Livewire::test(EditCourse::class, ['record' => $course->getRouteKey()])
        ->fillForm(['title' => 'Lớp 3 mới'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($course->refresh()->title)->toBe('Lớp 3 mới');
});

it('stores the uploaded thumbnail on the public disk', function () {
    Storage::fake('public');

    Livewire::test(CreateCourse::class)
        ->fillForm([
            'title' => 'Có ảnh',
            'thumbnail' => UploadedFile::fake()->image('cover.png'),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $course = Course::firstWhere('title', 'Có ảnh');

    expect($course->thumbnail)->toStartWith('courses/');
    Storage::disk('public')->assertExists($course->thumbnail);
});

it('rejects a thumbnail that is not an image', function () {
    Storage::fake('public');

    Livewire::test(CreateCourse::class)
        ->fillForm([
            'title' => 'Sai ảnh',
            'thumbnail' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
        ])
        ->call('create')
        ->assertHasFormErrors(['thumbnail']);
});

it('makes a course free by default and lets the admin set its monthly price', function () {
    Livewire::test(CreateCourse::class)
        ->fillForm(['title' => 'Khóa miễn phí', 'sort_order' => 1])
        ->call('create')
        ->assertHasNoFormErrors();

    Livewire::test(CreateCourse::class)
        ->fillForm(['title' => 'Khóa có phí', 'sort_order' => 2, 'monthly_price' => 50000])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Course::firstWhere('title', 'Khóa miễn phí')->isFree())->toBeTrue()
        ->and(Course::firstWhere('title', 'Khóa có phí')->monthly_price)->toBe(50000);
});

it('changes the price of a course and rejects a negative or fractional one', function () {
    $course = Course::factory()->priced(50000)->create();

    Livewire::test(EditCourse::class, ['record' => $course->getRouteKey()])
        ->fillForm(['monthly_price' => 70000])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($course->refresh()->monthly_price)->toBe(70000);

    foreach ([-1, 1500.5] as $invalid) {
        Livewire::test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->fillForm(['monthly_price' => $invalid])
            ->call('save')
            ->assertHasFormErrors(['monthly_price']);
    }

    expect($course->refresh()->monthly_price)->toBe(70000);
});

it('saves the unit count, lesson count and level of a course, each optional', function () {
    Livewire::test(CreateCourse::class)
        ->fillForm(['title' => 'Đủ thông tin', 'sort_order' => 1, 'unit_count' => 10, 'lesson_count' => 40, 'level' => 'Cơ bản'])
        ->call('create')
        ->assertHasNoFormErrors();

    Livewire::test(CreateCourse::class)
        ->fillForm(['title' => 'Chưa nhập', 'sort_order' => 2])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Course::firstWhere('title', 'Đủ thông tin'))->unit_count->toBe(10)->lesson_count->toBe(40)->level->toBe('Cơ bản')
        ->and(Course::firstWhere('title', 'Chưa nhập'))->unit_count->toBeNull()->lesson_count->toBeNull()->level->toBeNull();
});

it('rejects a negative count or a level that is too long', function () {
    Livewire::test(CreateCourse::class)
        ->fillForm(['title' => 'Sai', 'sort_order' => 1, 'unit_count' => -1, 'lesson_count' => 70000, 'level' => str_repeat('a', 51)])
        ->call('create')
        ->assertHasFormErrors(['unit_count', 'lesson_count', 'level']);
});

it('shows the price of each course in the list, Free for a free one', function () {
    $free = Course::factory()->create();
    $paid = Course::factory()->priced(50000)->create();

    Livewire::test(ListCourses::class)
        ->assertCanSeeTableRecords([$free, $paid])
        ->assertTableColumnStateSet('monthly_price', 50000, $paid)
        ->assertSee('50.000 ₫')
        ->assertSee('Free');
});

it('saves a regular price above the monthly price and rejects one that is not', function () {
    $course = Course::factory()->priced(50000)->create();

    Livewire::test(EditCourse::class, ['record' => $course->getRouteKey()])
        ->fillForm(['regular_price' => 100000])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($course->refresh()->regular_price)->toBe(100000)
        ->and($course->displayDiscountPercent())->toBe(50);

    Livewire::test(EditCourse::class, ['record' => $course->getRouteKey()])
        ->fillForm(['regular_price' => 40000])
        ->call('save')
        ->assertHasFormErrors(['regular_price']);
});
