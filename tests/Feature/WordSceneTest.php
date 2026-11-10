<?php

use App\Filament\Resources\WordScenes\Pages\ListWordScenes;
use App\Models\User;
use App\Models\Word;
use App\Models\WordScene;
use App\Models\WordSceneItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

it('reuses the stored word and redraws a word the scene already has', function () {
    $scene = WordScene::factory()->create();
    $apple = Word::factory()->create(['word' => 'apple']);

    $first = $scene->placeWord(' Apple ', ['x' => 0.1, 'y' => 0.2, 'width' => 0.3, 'height' => 0.4]);
    $again = $scene->placeWord('APPLE', ['x' => 0.5, 'y' => 0.5, 'width' => 0.2, 'height' => 0.2]);
    $banana = $scene->placeWord('banana', ['x' => 0, 'y' => 0, 'width' => 1, 'height' => 1]);

    expect($first->word_id)->toBe($apple->id)
        ->and($again->is($first))->toBeTrue()
        ->and($again->fresh())->x->toBe(0.5)
        ->and($banana->word->word)->toBe('banana')
        ->and($scene->items()->count())->toBe(2);
});

it('lists the word scenes in the panel, linking to the controller pages', function () {
    $this->actingAs(User::factory()->admin()->create());
    $scene = WordScene::factory()->create();

    Livewire::test(ListWordScenes::class)
        ->assertCanSeeTableRecords([$scene])
        ->assertSee(route('admin.word-scenes.create'))
        ->assertSee(route('admin.word-scenes.edit', $scene));
});

it('keeps the admin pages for admins', function () {
    $scene = WordScene::factory()->create();

    $this->get(route('admin.word-scenes.create'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create());

    $this->get(route('admin.word-scenes.create'))->assertForbidden();
    $this->get(route('admin.word-scenes.edit', $scene))->assertForbidden();
    $this->postJson(route('admin.word-scenes.items.store', $scene), ['word' => 'apple', 'x' => 0, 'y' => 0, 'width' => 1, 'height' => 1])->assertForbidden();
});

describe('as an admin', function () {
    beforeEach(function () {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
    });

    it('creates a scene from a title and a picture, then opens its editor', function () {
        $this->get(route('admin.word-scenes.create'))->assertOk();

        $this->post(route('admin.word-scenes.store'), ['title' => 'Fruits', 'image' => UploadedFile::fake()->image('fruits.png')])
            ->assertRedirect();

        $scene = WordScene::firstWhere('title', 'Fruits');

        Storage::disk('public')->assertExists($scene->image);
        $this->get(route('admin.word-scenes.edit', $scene))->assertOk()->assertSee('data-word-scene-editor', false)->assertSee($scene->image_url);
    });

    it('requires a title and a picture to create a scene', function () {
        $this->post(route('admin.word-scenes.store'), ['title' => ''])->assertSessionHasErrors(['title', 'image']);
    });

    it('hands the words of the scene to the editor', function () {
        $item = WordSceneItem::factory()->create();

        $this->get(route('admin.word-scenes.edit', $item->word_scene_id))
            ->assertOk()
            ->assertSee(e(json_encode($item->word->word)), false);
    });

    it('renames a scene and replaces its picture, clearing the rectangles drawn on the old one', function () {
        Storage::disk('public')->put('word-scenes/old.png', 'old');
        $scene = WordScene::factory()->create(['image' => 'word-scenes/old.png']);
        $item = WordSceneItem::factory()->for($scene)->create();

        $this->put(route('admin.word-scenes.update', $scene), ['title' => 'New', 'image' => UploadedFile::fake()->image('new.png')])
            ->assertRedirect(route('admin.word-scenes.edit', $scene));

        $scene->refresh();

        expect($scene->title)->toBe('New')
            ->and($scene->image)->not->toBe('word-scenes/old.png')
            ->and($item->fresh()->hasRectangle())->toBeFalse();
        Storage::disk('public')->assertExists($scene->image);
        Storage::disk('public')->assertMissing('word-scenes/old.png');
    });

    it('keeps the picture and the rectangles when only the title changes', function () {
        $scene = WordScene::factory()->create(['image' => 'word-scenes/old.png']);
        $item = WordSceneItem::factory()->for($scene)->create();

        $this->put(route('admin.word-scenes.update', $scene), ['title' => 'New'])->assertRedirect();

        expect($scene->fresh())->title->toBe('New')->image->toBe('word-scenes/old.png')
            ->and($item->fresh()->hasRectangle())->toBeTrue();
    });

    it('saves a typed word with the rectangle drawn on the picture', function () {
        $scene = WordScene::factory()->create();
        $apple = Word::factory()->create(['word' => 'apple']);

        $this->postJson(route('admin.word-scenes.items.store', $scene), ['word' => ' Apple ', 'x' => 0.1, 'y' => 0.2, 'width' => 0.3, 'height' => 0.4])
            ->assertOk()
            ->assertJsonPath('word', 'apple')
            ->assertJsonPath('x', 0.1)
            ->assertJsonPath('height', 0.4);

        expect($scene->items->pluck('word_id')->all())->toBe([$apple->id]);
    });

    it('rejects a blank word and a rectangle outside the picture', function () {
        $scene = WordScene::factory()->create();

        $this->postJson(route('admin.word-scenes.items.store', $scene), ['word' => '  ', 'x' => 0.1, 'y' => 0.2, 'width' => 0.3, 'height' => 0.4])
            ->assertJsonValidationErrors('word');
        $this->postJson(route('admin.word-scenes.items.store', $scene), ['word' => 'apple', 'x' => 1.2, 'y' => 0.2, 'width' => 0, 'height' => 0.4])
            ->assertJsonValidationErrors(['x', 'width']);

        expect($scene->items)->toBeEmpty();
    });

    it('removes a word of the scene and only of that scene', function () {
        $item = WordSceneItem::factory()->create();
        $other = WordScene::factory()->create();

        $this->deleteJson(route('admin.word-scenes.items.destroy', [$other, $item]))->assertNotFound();
        $this->deleteJson(route('admin.word-scenes.items.destroy', [$item->word_scene_id, $item]))->assertNoContent();

        expect(WordSceneItem::count())->toBe(0);
    });
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
