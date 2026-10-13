<?php

use App\Filament\Resources\Tags\Pages\CreateTag;
use App\Filament\Resources\Tags\Pages\EditTag;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('generates the slug from the name', function () {
    Livewire::test(CreateTag::class)
        ->fillForm(['name' => 'Daily Life'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Tag::firstWhere('name', 'Daily Life'))->slug->toBe('daily-life');
});

it('requires a name and a unique slug', function () {
    Tag::factory()->create(['slug' => 'taken']);

    Livewire::test(CreateTag::class)
        ->fillForm(['name' => '', 'slug' => 'taken'])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required', 'slug' => 'unique']);
});

it('allows keeping the own slug when editing a tag', function () {
    $tag = Tag::factory()->create(['slug' => 'food']);

    Livewire::test(EditTag::class, ['record' => $tag->getRouteKey()])
        ->fillForm(['name' => 'Food & Drinks'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($tag->fresh())->name->toBe('Food & Drinks')->slug->toBe('food');
});
