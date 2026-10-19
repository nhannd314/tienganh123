<?php

use App\Filament\Resources\Sentences\Pages\CreateSentence;
use App\Filament\Resources\Sentences\Pages\EditSentence;
use App\Filament\Resources\Sentences\Pages\ListSentences;
use App\Filament\Resources\SentenceSets\Pages\CreateSentenceSet;
use App\Filament\Resources\SentenceSets\Pages\EditSentenceSet;
use App\Filament\Resources\SentenceSets\Pages\ListSentenceSets;
use App\Filament\Resources\SentenceSets\RelationManagers\SentencesRelationManager;
use App\Jobs\GenerateSentenceAudio;
use App\Jobs\GenerateSentenceImage;
use App\Models\Sentence;
use App\Models\SentenceSet;
use App\Models\Tag;
use App\Models\User;
use App\Services\OpenAiImageGenerator;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('creates a sentence set and generates its slug from the title', function () {
    Livewire::test(CreateSentenceSet::class)
        ->fillForm(['title' => 'Trái cây', 'sort_order' => 1])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(SentenceSet::firstWhere('title', 'Trái cây')->slug)->toBe('trai-cay');
});

it('lists the sentence sets with the number of their sentences', function () {
    $set = SentenceSet::factory()->has(Sentence::factory()->count(2))->create();

    Livewire::test(ListSentenceSets::class)
        ->assertCanSeeTableRecords([$set])
        ->assertTableColumnStateSet('sentences_count', 2, $set);
});

it('creates a sentence in several sets, or in none', function () {
    [$first, $second] = SentenceSet::factory()->count(2)->create();

    Livewire::test(CreateSentence::class)
        ->fillForm(['sentence' => 'I have an `apple`.', 'translation' => 'Tôi có 1 quả táo.', 'sentenceSets' => [$first->id, $second->id]])
        ->call('create')
        ->assertHasNoFormErrors();

    Livewire::test(CreateSentence::class)
        ->fillForm(['sentence' => 'No set.'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Sentence::firstWhere('sentence', 'I have an `apple`.'))
        ->translation->toBe('Tôi có 1 quả táo.')
        ->sentenceSets->pluck('id')->all()->toBe([$first->id, $second->id])
        ->and(Sentence::firstWhere('sentence', 'No set.')->sentenceSets)->toBeEmpty();
});

it('rejects a sentence or meaning longer than 500 characters', function () {
    Livewire::test(CreateSentence::class)
        ->fillForm(['sentence' => str_repeat('a', 501), 'translation' => str_repeat('a', 501)])
        ->call('create')
        ->assertHasFormErrors(['sentence' => 'max', 'translation' => 'max']);
});

it('filters the sentences by set', function () {
    [$inSet, $other] = Sentence::factory()->count(2)->create();
    $set = SentenceSet::factory()->hasAttached($inSet, ['sort_order' => 1], 'sentences')->create();

    Livewire::test(ListSentences::class)
        ->assertCanSeeTableRecords([$inSet, $other])
        ->filterTable('sentenceSets', $set->id)
        ->assertCanSeeTableRecords([$inSet])
        ->assertCanNotSeeTableRecords([$other]);
});

it('lists in order and reorders the sentences of a set from its page', function () {
    $set = SentenceSet::factory()->create();
    [$first, $second] = Sentence::factory()->count(2)->create();
    $set->sentences()->attach([$first->id => ['sort_order' => 1], $second->id => ['sort_order' => 2]]);

    $manager = fn () => Livewire::test(SentencesRelationManager::class, ['ownerRecord' => $set, 'pageClass' => EditSentenceSet::class]);

    $manager()
        ->assertCanSeeTableRecords([$first, $second], inOrder: true);

    $manager()->call('reorderTable', [$second->id, $first->id]);

    expect($set->sentences()->pluck('sentences.id')->all())->toBe([$second->id, $first->id]);
});

it('queues the Azure audio of a saved sentence that has none, and warns when Azure is not configured', function () {
    Queue::fake();
    config(['services.azure_speech.key' => 'speech-key', 'services.azure_speech.region' => 'southeastasia']);

    Livewire::test(CreateSentence::class)
        ->fillForm(['sentence' => 'Hello.'])
        ->call('create')
        ->assertNotified('The sentence audio is being generated');

    Queue::assertPushed(GenerateSentenceAudio::class, fn (GenerateSentenceAudio $job) => $job->sentence->sentence === 'Hello.');

    config(['services.azure_speech.key' => null]);

    Livewire::test(CreateSentence::class)
        ->fillForm(['sentence' => 'Hi.'])
        ->call('create')
        ->assertNotified('The sentence audio was not generated');

    Queue::assertPushed(GenerateSentenceAudio::class, 1);
});

it('has no Add sentence button on the sentences of a set, and no sentence lines or image checkbox on its form', function () {
    $set = SentenceSet::factory()->create();

    Livewire::test(SentencesRelationManager::class, ['ownerRecord' => $set, 'pageClass' => EditSentenceSet::class])
        ->assertActionDoesNotExist(TestAction::make('create')->table())
        ->assertActionExists(TestAction::make('addSentences')->table())
        ->assertActionExists(TestAction::make('attach')->table());

    Livewire::test(EditSentenceSet::class, ['record' => $set->getRouteKey()])
        ->assertFormFieldDoesNotExist('sentence_lines')
        ->assertFormFieldDoesNotExist('generate_sentence_images');
});

it('adds a sentence for each line of the popup with its meaning, after the existing ones, and queues their audio', function () {
    Queue::fake();
    config(['services.azure_speech.key' => 'speech-key', 'services.azure_speech.region' => 'southeastasia']);
    $set = SentenceSet::factory()->create();
    $set->sentences()->attach(Sentence::factory()->create(['sentence' => 'Already here.', 'translation' => null]), ['sort_order' => 4]);

    Livewire::test(SentencesRelationManager::class, ['ownerRecord' => $set, 'pageClass' => EditSentenceSet::class])
        ->callAction(TestAction::make('addSentences')->table(), ['sentence_lines' => "I have an `apple` | Tôi có 1 quả táo.\n\nHello there.\nAlready here. | Bỏ qua\nI have an `apple` | Lặp"])
        ->assertHasNoFormErrors()
        ->assertNotified('The sentence audio is being generated');

    expect($set->sentences->map(fn (Sentence $sentence): array => [$sentence->sentence, $sentence->translation, $sentence->pivot->sort_order])->all())->toBe([
        ['Already here.', null, 4],
        ['I have an `apple`', 'Tôi có 1 quả táo.', 5],
        ['Hello there.', null, 6],
    ]);

    Queue::assertPushed(GenerateSentenceAudio::class, 2);
    Queue::assertNotPushed(GenerateSentenceImage::class);
});

it('queues an OpenAI image for each added sentence only when the checkbox of the popup is ticked and OpenAI is configured', function () {
    Queue::fake();
    config(['services.azure_speech.key' => null, 'services.openai.key' => 'openai-key']);
    $set = SentenceSet::factory()->create();
    $manager = fn () => Livewire::test(SentencesRelationManager::class, ['ownerRecord' => $set, 'pageClass' => EditSentenceSet::class]);

    $manager()
        ->callAction(TestAction::make('addSentences')->table(), ['sentence_lines' => "A | Một\nB", 'generate_sentence_images' => true])
        ->assertHasNoFormErrors()
        ->assertNotified('The sentence images are being generated');

    Queue::assertPushed(GenerateSentenceImage::class, 2);
    Queue::assertNotPushed(GenerateSentenceAudio::class);

    $manager()->callAction(TestAction::make('addSentences')->table(), ['sentence_lines' => 'C']);

    Queue::assertPushed(GenerateSentenceImage::class, 2);

    config(['services.openai.key' => null]);

    $manager()
        ->callAction(TestAction::make('addSentences')->table(), ['sentence_lines' => 'D', 'generate_sentence_images' => true])
        ->assertNotified('The sentence images were not generated');

    Queue::assertPushed(GenerateSentenceImage::class, 2);
});

it('rejects a line without a sentence or longer than 500 characters, and an empty popup', function () {
    $set = SentenceSet::factory()->create();

    foreach (['| only meaning', str_repeat('a', 501), 'Short | '.str_repeat('a', 501), ''] as $lines) {
        Livewire::test(SentencesRelationManager::class, ['ownerRecord' => $set, 'pageClass' => EditSentenceSet::class])
            ->callAction(TestAction::make('addSentences')->table(), ['sentence_lines' => $lines])
            ->assertHasFormErrors(['sentence_lines']);
    }

    expect($set->sentences()->count())->toBe(0);
});

it('attaches an existing sentence to a set, also when it already belongs to another set, and detaches it without deleting it', function () {
    $set = SentenceSet::factory()->create();
    $sentence = Sentence::factory()->hasAttached(SentenceSet::factory(), ['sort_order' => 1], 'sentenceSets')->create();
    $manager = fn () => Livewire::test(SentencesRelationManager::class, ['ownerRecord' => $set, 'pageClass' => EditSentenceSet::class]);

    $manager()
        ->callAction(TestAction::make('attach')->table(), ['recordId' => $sentence->id])
        ->assertHasNoFormErrors();

    expect($sentence->sentenceSets()->count())->toBe(2);

    $manager()->callAction(TestAction::make('detach')->table($sentence));

    expect($sentence->sentenceSets()->count())->toBe(1)
        ->and(Sentence::find($sentence->id))->not->toBeNull();
});
it('stores the OpenAI image of a sentence that has none, and leaves one that has an image alone', function () {
    Storage::fake('public');
    config(['services.openai.key' => 'openai-key']);
    $generator = Mockery::mock(OpenAiImageGenerator::class);
    $generator->shouldReceive('generateForSentence')->once()->with('I eat an apple.')->andReturn('PNG-BYTES');
    $sentence = Sentence::factory()->create(['sentence' => 'I eat an `apple`.', 'image' => null]);
    $withImage = Sentence::factory()->create(['image' => 'lessons/sentence-images/a.png']);

    (new GenerateSentenceImage($sentence))->handle($generator);
    (new GenerateSentenceImage($withImage))->handle($generator);

    $stored = $sentence->fresh()->image;

    expect($stored)->toStartWith('lessons/sentence-images/')->toEndWith('.png')
        ->and(Storage::disk('public')->get($stored))->toBe('PNG-BYTES')
        ->and($withImage->fresh()->image)->toBe('lessons/sentence-images/a.png');
});

it('retries the sentence image job when OpenAI gives no image', function () {
    config(['services.openai.key' => 'openai-key']);
    $generator = Mockery::mock(OpenAiImageGenerator::class);
    $generator->shouldReceive('generateForSentence')->andReturnNull();

    expect(fn () => (new GenerateSentenceImage(Sentence::factory()->create()))->handle($generator))
        ->toThrow(RuntimeException::class);
});

it('queues a replacing OpenAI image when the Tạo ảnh AI button of the sentence form is pressed, and warns when OpenAI is not configured', function () {
    Queue::fake();
    config(['services.openai.key' => 'openai-key']);
    $sentence = Sentence::factory()->create(['image' => 'lessons/sentence-images/old.png']);
    Livewire::test(EditSentence::class, ['record' => $sentence->getRouteKey()])
        ->callAction(TestAction::make('generateImage')->schemaComponent('form-actions', schema: 'content'))
        ->assertNotified('The sentence image is being generated');

    Queue::assertPushed(GenerateSentenceImage::class, fn (GenerateSentenceImage $job) => $job->sentence->is($sentence) && $job->replace);

    Livewire::test(EditSentence::class, ['record' => $sentence->getRouteKey()])
        ->call('save')
        ->assertHasNoFormErrors();

    Queue::assertPushed(GenerateSentenceImage::class, 1);

    config(['services.openai.key' => null]);

    Livewire::test(EditSentence::class, ['record' => $sentence->getRouteKey()])
        ->callAction(TestAction::make('generateImage')->schemaComponent('form-actions', schema: 'content'))
        ->assertNotified('The sentence image was not generated');

    Queue::assertPushed(GenerateSentenceImage::class, 1);
});

it('replaces the image of a sentence and deletes the old file when the job is to replace it', function () {
    $disk = Storage::fake('public');
    $disk->put('lessons/sentence-images/old.png', 'OLD');
    config(['services.openai.key' => 'openai-key']);
    $generator = Mockery::mock(OpenAiImageGenerator::class);
    $generator->shouldReceive('generateForSentence')->once()->andReturn('NEW');
    $sentence = Sentence::factory()->create(['image' => 'lessons/sentence-images/old.png']);

    (new GenerateSentenceImage($sentence, replace: true))->handle($generator);

    $stored = $sentence->fresh()->image;

    expect($stored)->not->toBe('lessons/sentence-images/old.png')
        ->and($disk->get($stored))->toBe('NEW');
    $disk->assertMissing('lessons/sentence-images/old.png');
});

it('puts the Tạo ảnh AI button after Save changes on the edit page, and not on the create page', function () {
    $sentence = Sentence::factory()->create();

    Livewire::test(EditSentence::class, ['record' => $sentence->getRouteKey()])
        ->assertSeeInOrder(['Save changes', 'Cancel', 'Tạo ảnh AI']);

    Livewire::test(CreateSentence::class)->assertDontSee('Tạo ảnh AI');
});

it('draws the image from the edit modal of the sentences of a set', function () {
    Queue::fake();
    config(['services.openai.key' => 'openai-key']);
    $set = SentenceSet::factory()->create();
    $sentence = Sentence::factory()->hasAttached($set, ['sort_order' => 1], 'sentenceSets')->create();

    Livewire::test(SentencesRelationManager::class, ['ownerRecord' => $set, 'pageClass' => EditSentenceSet::class])
        ->callAction([TestAction::make('edit')->table($sentence), 'generateImage']);

    Queue::assertPushed(GenerateSentenceImage::class, fn (GenerateSentenceImage $job) => $job->sentence->is($sentence) && $job->replace);
});

it('files a sentence set under any number of tags, and lets go of a tag that is deleted', function () {
    $tag = Tag::factory()->create();
    $otherTag = Tag::factory()->create();

    Livewire::test(CreateSentenceSet::class)
        ->fillForm(['title' => 'Có tag', 'sort_order' => 1, 'tags' => [$tag->id, $otherTag->id]])
        ->call('create')
        ->assertHasNoFormErrors();
    Livewire::test(CreateSentenceSet::class)
        ->fillForm(['title' => 'Không tag', 'sort_order' => 2])
        ->call('create')
        ->assertHasNoFormErrors();

    $tagged = SentenceSet::firstWhere('title', 'Có tag');

    expect($tagged->tags->modelKeys())->toEqualCanonicalizing([$tag->id, $otherTag->id])
        ->and(SentenceSet::firstWhere('title', 'Không tag')->tags)->toBeEmpty()
        ->and($tag->sentenceSets->modelKeys())->toBe([$tagged->id]);

    $tag->delete();

    expect($tagged->fresh()->tags->modelKeys())->toBe([$otherTag->id]);
});

it('orders the sentences of a set by a qualified created_at, as the pivot table has the same column', function () {
    $set = SentenceSet::factory()->create();
    $first = Sentence::factory()->hasAttached($set, ['sort_order' => 1], 'sentenceSets')->create();
    $second = Sentence::factory()->hasAttached($set, ['sort_order' => 2], 'sentenceSets')->create();

    $manager = Livewire::test(SentencesRelationManager::class, ['ownerRecord' => $set, 'pageClass' => EditSentenceSet::class]);

    expect($manager->instance()->getTable()->getDefaultSortColumn())->toBe('sentences.created_at');
    $manager->assertCanSeeTableRecords([$first, $second], inOrder: true);
});
