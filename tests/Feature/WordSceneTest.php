<?php

use App\Filament\Resources\WordScenes\Pages\CreateWordScene;
use App\Filament\Resources\WordScenes\Pages\EditWordScene;
use App\Jobs\GenerateWordSceneImage;
use App\Models\User;
use App\Models\Word;
use App\Models\WordScene;
use App\Models\WordSceneItem;
use App\Services\OpenAiImageGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('keeps the words of a scene with the rectangle around each object', function () {
    $scene = WordScene::factory()->create();
    $drawn = WordSceneItem::factory()->for($scene)->create(['sort_order' => 1]);
    WordSceneItem::factory()->for($scene)->undrawn()->create(['sort_order' => 2]);

    expect($scene->items)->toHaveCount(2)
        ->and($scene->drawnItems->pluck('id')->all())->toBe([$drawn->id])
        ->and($drawn->fresh())->hasRectangle()->toBeTrue()->x->toBe(0.1)->height->toBe(0.4);
});

it('creates a scene with its words and queues a picture when none is given', function () {
    Queue::fake();
    config(['services.openai.key' => 'test-key']);
    $this->actingAs(User::factory()->create());
    $word = Word::factory()->create();

    Livewire::test(CreateWordScene::class)
        ->fillForm(['title' => 'Fruits', 'items' => [['word_id' => $word->id]]])
        ->call('create')
        ->assertHasNoFormErrors();

    $scene = WordScene::firstWhere('title', 'Fruits');

    expect($scene->items->pluck('word_id')->all())->toBe([$word->id]);
    Queue::assertPushed(GenerateWordSceneImage::class, fn (GenerateWordSceneImage $job): bool => $job->wordScene->is($scene));
});

it('saves the rectangle of a word from the edit form', function () {
    $this->actingAs(User::factory()->create());
    $item = WordSceneItem::factory()->undrawn()->create();

    Livewire::test(EditWordScene::class, ['record' => $item->word_scene_id])
        ->fillForm(['items' => ["record-{$item->id}" => ['word_id' => $item->word_id, 'x' => 0.25, 'y' => 0.5, 'width' => 0.1, 'height' => 0.2]]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($item->fresh())->hasRectangle()->toBeTrue()->x->toBe(0.25)->width->toBe(0.1);
});

it('replaces the picture of a scene with a generated one and clears the old rectangles', function () {
    Storage::fake('public');
    config(['services.openai.key' => 'test-key']);
    Storage::disk('public')->put('word-scenes/old.png', 'old');
    $scene = WordScene::factory()->create(['image' => 'word-scenes/old.png']);
    $item = WordSceneItem::factory()->for($scene)->create();

    $generator = Mockery::mock(OpenAiImageGenerator::class);
    $generator->shouldReceive('generateForWords')->once()->with([$item->word->word])->andReturn('png-bytes');

    (new GenerateWordSceneImage($scene))->handle($generator);

    $scene->refresh();

    expect($scene->image)->not->toBe('word-scenes/old.png');
    Storage::disk('public')->assertExists($scene->image);
    Storage::disk('public')->assertMissing('word-scenes/old.png');
    expect($item->fresh()->hasRectangle())->toBeFalse();
});

it('renders a clickable audio button over each drawn object', function () {
    Storage::fake('public');
    $scene = WordScene::factory()->create(['image' => 'word-scenes/a.png']);
    $word = Word::factory()->create(['word' => 'apple']);
    WordSceneItem::factory()->for($scene)->for($word)->create(['x' => 0.1, 'y' => 0.2, 'width' => 0.3, 'height' => 0.4]);
    WordSceneItem::factory()->for($scene)->undrawn()->create();

    $html = view('components.word-scene', ['scene' => $scene])->render();

    expect(substr_count($html, 'data-audio-button'))->toBe(1)
        ->and($html)->toContain('data-text="apple"', 'left: 10%', 'top: 20%', 'width: 30%', 'height: 40%');
});
