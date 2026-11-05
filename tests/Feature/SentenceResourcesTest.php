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
    $set->sentences()->attach(Sentence::factory()->create(['sentence' => 'Already here.', 'translation' => null, 'audio' => 'here.mp3']), ['sort_order' => 4]);

    Livewire::test(SentencesRelationManager::class, ['ownerRecord' => $set, 'pageClass' => EditSentenceSet::class])
        ->callAction(TestAction::make('addSentences')->table(), ['sentence_lines' => "I have an `apple` | Tôi có 1 quả táo.\n\nHello there.\nAlready here. | Bỏ qua\nI have an `apple` | Lặp"])
        ->assertHasNoFormErrors()
        ->assertNotified('The sentence audio is being generated');

    expect($set->sentences->map(fn (Sentence $sentence): array => [$sentence->sentence, $sentence->translation, $sentence->pivot->sort_order])->all())->toBe([
        ['Already here.', 'Bỏ qua', 4],
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

it('updates a sentence with the same words instead of adding it again, and keeps its meaning when none is typed', function () {
    Queue::fake();
    $set = SentenceSet::factory()->create();
    $feed = Sentence::factory()->create(['sentence' => 'I feed the pets.', 'translation' => 'Tôi cho thú cưng ăn.']);
    $other = Sentence::factory()->create(['sentence' => 'We `swim` fast.', 'translation' => 'Cũ']);

    Livewire::test(SentencesRelationManager::class, ['ownerRecord' => $set, 'pageClass' => EditSentenceSet::class])
        ->callAction(TestAction::make('addSentences')->table(), ['sentence_lines' => 'I `feed` the pets.
We swim fast. | Mới'])
        ->assertHasNoFormErrors();

    expect(Sentence::count())->toBe(2)
        ->and($feed->fresh()->only(['sentence', 'translation']))->toBe(['sentence' => 'I `feed` the pets.', 'translation' => 'Tôi cho thú cưng ăn.'])
        ->and($other->fresh()->only(['sentence', 'translation']))->toBe(['sentence' => 'We swim fast.', 'translation' => 'Mới'])
        ->and($set->sentences()->count())->toBe(2);

    Queue::assertNothingPushed();
});

it('updates a sentence with the same words from the create form instead of creating a new one', function () {
    Queue::fake();
    $feed = Sentence::factory()->create(['sentence' => 'I feed the pets.', 'translation' => 'Tôi cho thú cưng ăn.']);

    Livewire::test(CreateSentence::class)
        ->fillForm(['sentence' => 'I `feed` the pets.', 'translation' => ''])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Sentence::count())->toBe(1)
        ->and($feed->fresh()->only(['sentence', 'translation']))->toBe(['sentence' => 'I `feed` the pets.', 'translation' => 'Tôi cho thú cưng ăn.']);
});

it('keeps the sets of a sentence updated from the create form, adds the chosen ones, and voices it again when its words change', function () {
    Queue::fake();
    config(['services.azure_speech.key' => 'speech-key', 'services.azure_speech.region' => 'southeastasia']);
    Storage::fake('public');
    Storage::disk('public')->put('lessons/sentence-audio/old.mp3', 'x');
    [$a, $b, $c] = SentenceSet::factory()->count(3)->create();
    $feed = Sentence::factory()->create(['sentence' => 'I feed the pets.', 'audio' => 'lessons/sentence-audio/old.mp3']);
    $feed->sentenceSets()->attach([$a->id, $b->id]);

    Livewire::test(CreateSentence::class)
        ->fillForm(['sentence' => 'i `feed` the Pets.', 'sentenceSets' => [$c->id]])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Sentence::count())->toBe(1)
        ->and($feed->fresh()->audio)->toBeNull()
        ->and($feed->sentenceSets()->pluck('sentence_sets.id')->sort()->values()->all())->toBe([$a->id, $b->id, $c->id]);
    Storage::disk('public')->assertMissing('lessons/sentence-audio/old.mp3');
    Queue::assertPushed(GenerateSentenceAudio::class, 1);
});

it('voices a sentence of the popup again only when its words change, not when only the backticks do', function () {
    Queue::fake();
    config(['services.azure_speech.key' => 'speech-key', 'services.azure_speech.region' => 'southeastasia']);
    $set = SentenceSet::factory()->create();
    Sentence::factory()->create(['sentence' => 'Same words.', 'audio' => 'keep.mp3']);

    Livewire::test(SentencesRelationManager::class, ['ownerRecord' => $set, 'pageClass' => EditSentenceSet::class])
        ->callAction(TestAction::make('addSentences')->table(), ['sentence_lines' => 'Same `words`.']);

    expect(Sentence::first()->audio)->toBe('keep.mp3');
    Queue::assertNothingPushed();
});

it('uses the sentence written exactly as typed when stored sentences differ only by their backticks', function () {
    Queue::fake();
    $set = SentenceSet::factory()->create();
    $plain = Sentence::factory()->create(['sentence' => "What's this?"]);
    $marked = Sentence::factory()->create(['sentence' => "What's `this`?", 'translation' => null]);

    Livewire::test(SentencesRelationManager::class, ['ownerRecord' => $set, 'pageClass' => EditSentenceSet::class])
        ->callAction(TestAction::make('addSentences')->table(), ['sentence_lines' => "What's `this`? | Đây là gì?"])
        ->assertHasNoFormErrors();

    expect($set->sentences()->pluck('sentences.id')->all())->toBe([$marked->id])
        ->and($marked->refresh()->translation)->toBe('Đây là gì?')
        ->and($plain->refresh()->sentence)->toBe("What's this?");
});

it('voices a sentence of the popup that is already stored without audio, but draws no image for it', function () {
    Queue::fake();
    config(['services.azure_speech.key' => 'speech-key', 'services.azure_speech.region' => 'southeastasia', 'services.openai.key' => 'openai-key']);
    $set = SentenceSet::factory()->create();
    $sentence = Sentence::factory()->create(['sentence' => 'No audio yet.', 'audio' => null]);

    Livewire::test(SentencesRelationManager::class, ['ownerRecord' => $set, 'pageClass' => EditSentenceSet::class])
        ->callAction(TestAction::make('addSentences')->table(), ['sentence_lines' => 'No audio yet.', 'generate_sentence_images' => true]);

    Queue::assertPushed(GenerateSentenceAudio::class, fn (GenerateSentenceAudio $job): bool => $job->sentence->is($sentence));
    Queue::assertNotPushed(GenerateSentenceImage::class);
});

it('drops the old audio and voices the sentence again when the edit page changes its words, not when only the backticks change', function () {
    Queue::fake();
    Storage::fake('public');
    config(['services.azure_speech.key' => 'speech-key', 'services.azure_speech.region' => 'southeastasia']);
    Storage::disk('public')->put('old.mp3', 'audio');
    $sentence = Sentence::factory()->create(['sentence' => 'Old words.', 'audio' => 'old.mp3']);

    $page = Livewire::test(EditSentence::class, ['record' => $sentence->getRouteKey()])
        ->fillForm(['sentence' => 'Old `words`.'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($sentence->refresh()->audio)->toBe('old.mp3');
    Queue::assertNothingPushed();

    $page->fillForm(['sentence' => 'New words.'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($sentence->refresh()->audio)->toBeNull();
    Storage::disk('public')->assertMissing('old.mp3');
    Queue::assertPushed(GenerateSentenceAudio::class, 1);

    $page->call('save')->assertHasNoFormErrors();

    expect($sentence->refresh()->audio)->toBeNull();
});

it('voices the sentence again when the edit modal of a set changes its words', function () {
    Queue::fake();
    Storage::fake('public');
    config(['services.azure_speech.key' => 'speech-key', 'services.azure_speech.region' => 'southeastasia']);
    Storage::disk('public')->put('old.mp3', 'audio');
    $set = SentenceSet::factory()->create();
    $sentence = Sentence::factory()->hasAttached($set, ['sort_order' => 1], 'sentenceSets')->create(['sentence' => 'Old words.', 'audio' => 'old.mp3']);

    Livewire::test(SentencesRelationManager::class, ['ownerRecord' => $set, 'pageClass' => EditSentenceSet::class])
        ->callAction(TestAction::make('edit')->table($sentence), ['sentence' => 'New words.'])
        ->assertHasNoFormErrors();

    expect($sentence->refresh()->audio)->toBeNull();
    Storage::disk('public')->assertMissing('old.mp3');
    Queue::assertPushed(GenerateSentenceAudio::class, 1);
});

it('keeps an audio saved along with the changed words', function () {
    $sentence = Sentence::factory()->create(['sentence' => 'Hello there', 'audio' => 'old.mp3']);

    $sentence->updateKeepingEmpty(['sentence' => 'hello there', 'audio' => 'new.mp3']);

    expect($sentence->refresh()->audio)->toBe('new.mp3');
});

it('refuses to edit a sentence into another one that differs only by backticks or capitals', function (string $typed) {
    Sentence::factory()->create(['sentence' => "What's `this`?"]);
    $other = Sentence::factory()->create(['sentence' => 'Other.']);

    Livewire::test(EditSentence::class, ['record' => $other->getRouteKey()])
        ->fillForm(['sentence' => $typed])
        ->call('save')
        ->assertHasFormErrors(['sentence']);

    expect($other->refresh()->sentence)->toBe('Other.');
})->with(["What's `this`?", "What's this?", "WHAT'S this?"]);

it('saves a sentence with its own words again, even when it already has a twin, unless it becomes that twin', function () {
    $plain = Sentence::factory()->create(['sentence' => "What's this?", 'translation' => null]);
    Sentence::factory()->create(['sentence' => "What's `this`?"]);

    $page = Livewire::test(EditSentence::class, ['record' => $plain->getRouteKey()])
        ->fillForm(['translation' => 'Đây là gì?'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($plain->refresh()->translation)->toBe('Đây là gì?');

    $page->fillForm(['sentence' => "What's `this`?"])
        ->call('save')
        ->assertHasFormErrors(['sentence']);
});

it('updates a sentence written exactly the same from the create form instead of refusing it', function () {
    $sentence = Sentence::factory()->create(['sentence' => 'Same `words`.', 'translation' => null]);

    Livewire::test(CreateSentence::class)
        ->fillForm(['sentence' => 'Same `words`.', 'translation' => 'Cùng từ'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Sentence::count())->toBe(1)
        ->and($sentence->refresh()->translation)->toBe('Cùng từ');
});

it('puts a sentence at the end of the set when it is attached, chosen on the create form or chosen on the edit form', function () {
    $set = SentenceSet::factory()->create();
    $set->sentences()->attach(Sentence::factory()->create(), ['sort_order' => 4]);
    $attached = Sentence::factory()->create();
    $edited = Sentence::factory()->create();

    Livewire::test(SentencesRelationManager::class, ['ownerRecord' => $set, 'pageClass' => EditSentenceSet::class])
        ->callAction(TestAction::make('attach')->table(), ['recordId' => $attached->id])
        ->assertHasNoFormErrors();

    Livewire::test(CreateSentence::class)
        ->fillForm(['sentence' => 'Brand new.', 'sentenceSets' => [$set->id]])
        ->call('create')
        ->assertHasNoFormErrors();

    Livewire::test(EditSentence::class, ['record' => $edited->getRouteKey()])
        ->fillForm(['sentenceSets' => [$set->id]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($set->sentences()->get()->map(fn (Sentence $sentence): int => $sentence->pivot->sort_order)->all())->toBe([4, 5, 6, 7])
        ->and($set->sentences()->pluck('sentences.id')->slice(1)->values()->all())->toBe([$attached->id, Sentence::firstWhere('sentence', 'Brand new.')->id, $edited->id]);
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
