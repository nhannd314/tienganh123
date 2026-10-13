<?php

use App\Models\Course;
use App\Models\Unit;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates a slug from the source attribute when slug is null', function () {
    $word = Word::factory()->create(['word' => 'ice cream', 'slug' => null]);

    expect($word->slug)->toBe('ice-cream');
});

it('keeps a slug that was provided', function () {
    $course = Course::factory()->create(['title' => 'Hello World', 'slug' => 'custom']);

    expect($course->slug)->toBe('custom');
});

it('appends incrementing suffixes when the slug already exists', function () {
    $titles = ['Hello', 'Hello!', 'Hello?'];

    $slugs = collect($titles)
        ->map(fn (string $title) => Course::factory()->create(['title' => $title, 'slug' => null])->slug)
        ->all();

    expect($slugs)->toBe(['hello', 'hello-1', 'hello-2']);
});

it('generates a slug on update when it is blank and ignores the record itself', function () {
    $course = Course::factory()->create(['title' => 'Grammar', 'slug' => 'grammar']);

    $course->update(['slug' => null]);

    expect($course->fresh()->slug)->toBe('grammar');
});

it('scopes uniqueness to the parent for units', function () {
    $units = Unit::factory()->count(2)->create(['title' => 'Intro', 'slug' => null]);
    $sameCourse = Unit::factory()->for($units[0]->course)->create(['title' => 'Intro', 'slug' => null]);

    expect($units[0]->slug)->toBe('intro')
        ->and($units[1]->slug)->toBe('intro')
        ->and($sameCourse->slug)->toBe('intro-1');
});
