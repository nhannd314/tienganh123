<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
    Filament::setCurrentPanel('admin');
});

it('groups the admin menu into learning content, vocabulary and pricing, in that order', function () {
    $groups = collect(Filament::getNavigation())->filter(fn ($group) => filled($group->getLabel()));

    expect($groups->map(fn ($group) => $group->getLabel())->values()->all())
        ->toBe(['Learning content', 'Vocabulary', 'Pricing']);

    $labelsOf = fn (string $group) => $groups->first(fn ($candidate) => $candidate->getLabel() === $group)
        ->getItems()
        ->map(fn ($item) => $item->getLabel())
        ->values()
        ->all();

    expect($labelsOf('Learning content'))->toBe(['Courses', 'Units', 'Lessons'])
        ->and($labelsOf('Vocabulary'))->toBe(['Words', 'Flashcard Sets', 'Vocabularies', 'Tags'])
        ->and($labelsOf('Pricing'))->toBe(['Membership plans', 'Token packages', 'AI services', 'Payments']);
});

it('gives every menu item its own icon', function () {
    $icons = collect(Filament::getNavigation())
        ->flatMap(fn ($group) => $group->getItems())
        ->filter(fn ($item) => $item->getLabel() !== 'Dashboard')
        ->map(fn ($item) => $item->getIcon());

    expect($icons)->toHaveCount(11)
        ->and($icons->unique())->toHaveCount(11)
        ->and($icons->filter(fn ($icon) => blank($icon)))->toBeEmpty();
});
