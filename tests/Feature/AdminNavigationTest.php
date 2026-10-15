<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
    Filament::setCurrentPanel('admin');
});

it('groups the admin menu into learning content, vocabulary, content and pricing, in that order', function () {
    $groups = collect(Filament::getNavigation())->filter(fn ($group) => filled($group->getLabel()));

    expect($groups->map(fn ($group) => $group->getLabel())->values()->all())
        ->toBe(['Learning content', 'Vocabulary', 'Content', 'Pricing']);

    $labelsOf = fn (string $group) => $groups->first(fn ($candidate) => $candidate->getLabel() === $group)
        ->getItems()
        ->map(fn ($item) => $item->getLabel())
        ->values()
        ->all();

    expect($labelsOf('Learning content'))->toBe(['Courses', 'Units', 'Lessons'])
        ->and($labelsOf('Vocabulary'))->toBe(['Words', 'Vocabularies', 'Flashcards', 'Flashcard Sets', 'Tags'])
        ->and($labelsOf('Content'))->toBe(['Categories', 'Posts', 'Pages'])
        ->and($labelsOf('Pricing'))->toBe(['Payments']);
});

it('gives every menu item its own icon', function () {
    $icons = collect(Filament::getNavigation())
        ->flatMap(fn ($group) => $group->getItems())
        ->filter(fn ($item) => $item->getLabel() !== 'Dashboard')
        ->map(fn ($item) => $item->getIcon());

    expect($icons)->toHaveCount(13)
        ->and($icons->unique())->toHaveCount(13)
        ->and($icons->filter(fn ($icon) => blank($icon)))->toBeEmpty();
});

it('links to the website under the dashboard, in a new tab', function () {
    $items = collect(Filament::getNavigation())->first(fn ($group) => blank($group->getLabel()))->getItems()->values();
    $website = $items->first(fn ($item) => $item->getLabel() === 'Website');

    expect($items->map(fn ($item) => $item->getLabel())->all())->toBe(['Dashboard', 'Website'])
        ->and($website->getUrl())->toBe(route('home'))
        ->and($website->shouldOpenUrlInNewTab())->toBeTrue();
});
