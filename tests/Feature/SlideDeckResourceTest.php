<?php

use App\Filament\Resources\SlideDecks\Pages\ListSlideDecks;
use App\Filament\Resources\SlideDecks\Pages\ViewSlideDeck;
use App\Models\Slide;
use App\Models\SlideDeck;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create());
});

it('lists the slideshows of all members with their maker and slide count', function () {
    $deck = SlideDeck::factory()->for(User::factory()->member()->create(['name' => 'Nguyễn Văn A']))->create(['title' => 'Gia đình']);
    Slide::factory()->count(2)->for($deck, 'deck')->sequence(['position' => 1], ['position' => 2])->create();

    Livewire::test(ListSlideDecks::class)
        ->assertCanSeeTableRecords([$deck])
        ->assertSee('Gia đình')
        ->assertSee('Nguyễn Văn A')
        ->assertTableColumnStateSet('slides_count', 2, $deck);
});

it('shows the slides of a slideshow and deletes it with its images', function () {
    $deck = SlideDeck::factory()->create();
    Slide::factory()->for($deck, 'deck')->create(['position' => 1, 'image' => 'slides/one.jpg', 'text' => 'Mother']);
    Storage::disk('public')->put('slides/one.jpg', 'x');

    Livewire::test(ViewSlideDeck::class, ['record' => $deck->getRouteKey()])
        ->assertOk()
        ->assertSee('Mother')
        ->callAction(DeleteAction::class);

    expect(SlideDeck::count())->toBe(0)
        ->and(Slide::count())->toBe(0);
    Storage::disk('public')->assertMissing('slides/one.jpg');
});

it('deletes the chosen slideshows in bulk with their images', function () {
    $decks = SlideDeck::factory()->count(2)->create();
    foreach ($decks as $deck) {
        Slide::factory()->for($deck, 'deck')->create(['position' => 1, 'image' => "slides/{$deck->id}.jpg"]);
        Storage::disk('public')->put("slides/{$deck->id}.jpg", 'x');
    }

    Livewire::test(ListSlideDecks::class)
        ->selectTableRecords($decks)
        ->callAction(TestAction::make('delete')->table()->bulk());

    expect(SlideDeck::count())->toBe(0);
    foreach ($decks as $deck) {
        Storage::disk('public')->assertMissing("slides/{$deck->id}.jpg");
    }
});
