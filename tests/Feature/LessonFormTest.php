<?php

use App\Filament\Resources\Lessons\Pages\CreateLesson;
use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Filament\Resources\Lessons\RelationManagers\FlashcardSetsRelationManager;
use App\Filament\Resources\Lessons\RelationManagers\VocabulariesRelationManager;
use App\Jobs\GenerateLessonSentenceAudio;
use App\Models\Course;
use App\Models\FlashcardSet;
use App\Models\Lesson;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vocabulary;
use Filament\Actions\AttachAction;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('generates the slug from the title and saves the passage and the rich content', function () {
    $unit = Unit::factory()->create();

    Livewire::test(CreateLesson::class)
        ->fillForm([
            'unit_id' => $unit->id,
            'title' => 'Say hello',
            'sort_order' => 1,
            'passage' => '<p>A <code>hello</code> story.</p>',
            'content' => '<p>Say hello.</p>',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Lesson::firstWhere('title', 'Say hello'))
        ->slug->toBe('say-hello')
        ->is_free->toBeFalse()
        ->is_published->toBeFalse()
        ->passage->toBe('<p>A <code>hello</code> story.</p>')
        ->content->toBe('<p>Say hello.</p>');
});

it('saves the example sentences with their meaning and image and queues the Azure audio of those that have none', function () {
    Queue::fake();
    Storage::fake('public');
    config(['services.azure_speech.key' => 'speech-key', 'services.azure_speech.region' => 'southeastasia']);
    $undoRepeaterFake = Repeater::fake();

    Livewire::test(CreateLesson::class)
        ->fillForm([
            'unit_id' => Unit::factory()->create()->id,
            'title' => 'Fruit',
            'example_sentences' => [
                ['sentence' => 'I eat an `apple`.', 'translation' => 'Tôi ăn một quả táo.', 'image' => [UploadedFile::fake()->image('apple.png')]],
                ['sentence' => 'Hello there.', 'translation' => null],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified('The sentence audio is being generated');

    $undoRepeaterFake();
    $lesson = Lesson::firstWhere('title', 'Fruit');

    expect($lesson->example_sentences)->toHaveCount(2)
        ->and($lesson->example_sentences[0])->toMatchArray(['sentence' => 'I eat an `apple`.', 'translation' => 'Tôi ăn một quả táo.', 'audio' => null])
        ->and($lesson->example_sentences[0]['image'])->toStartWith('lessons/sentence-images/')
        ->and($lesson->example_sentences[1])->toMatchArray(['sentence' => 'Hello there.', 'translation' => null, 'image' => null]);

    Storage::disk('public')->assertExists($lesson->example_sentences[0]['image']);
    Queue::assertPushed(GenerateLessonSentenceAudio::class, fn (GenerateLessonSentenceAudio $job) => $job->lesson->is($lesson));
});

it('does not queue the sentence audio when every sentence has audio or Azure is not configured', function () {
    Queue::fake();
    Storage::fake('public')->put('lessons/sentence-audio/hi.mp3', 'MP3-BYTES');
    config(['services.azure_speech.key' => 'speech-key', 'services.azure_speech.region' => 'southeastasia']);
    $withAudio = Lesson::factory()->create(['example_sentences' => [['sentence' => 'Hi.', 'audio' => 'lessons/sentence-audio/hi.mp3']]]);

    Livewire::test(EditLesson::class, ['record' => $withAudio->getRouteKey()])->call('save')->assertHasNoFormErrors();

    config(['services.azure_speech.key' => null]);
    $withoutAudio = Lesson::factory()->create(['example_sentences' => [['sentence' => 'Hello.']]]);

    Livewire::test(EditLesson::class, ['record' => $withoutAudio->getRouteKey()])
        ->call('save')
        ->assertNotified('The sentence audio was not generated');

    Queue::assertNothingPushed();
});

it('keeps the audio of an example sentence whose text is unchanged', function () {
    Queue::fake();
    $lesson = Lesson::factory()->create(['example_sentences' => [
        ['sentence' => 'Old text.', 'translation' => 'Cũ', 'audio' => 'lessons/sentence-audio/old.mp3'],
        ['sentence' => 'Same text.', 'translation' => null, 'audio' => 'lessons/sentence-audio/same.mp3'],
    ]]);
    $undoRepeaterFake = Repeater::fake();

    Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
        ->assertSchemaStateSet(['example_sentences' => [
            ['sentence' => 'Old text.', 'translation' => 'Cũ', 'audio' => 'lessons/sentence-audio/old.mp3', 'image' => []],
            ['sentence' => 'Same text.', 'translation' => null, 'audio' => 'lessons/sentence-audio/same.mp3', 'image' => []],
        ]])
        ->fillForm(['example_sentences' => [
            ['sentence' => 'New text.', 'translation' => 'Mới'],
            ['sentence' => 'Same text.', 'translation' => 'Giống'],
            ['sentence' => 'Added.', 'translation' => null],
        ]])
        ->call('save')
        ->assertHasNoFormErrors();

    $undoRepeaterFake();

    expect(array_column($lesson->fresh()->example_sentences, 'audio'))->toBe([null, 'lessons/sentence-audio/same.mp3', null]);
});

it('deletes the image of an example sentence that is removed or whose image is replaced', function () {
    Queue::fake();
    $disk = Storage::fake('public');
    foreach (['removed', 'replaced', 'kept'] as $name) {
        $disk->put("lessons/sentence-images/{$name}.png", 'PNG');
    }
    $lesson = Lesson::factory()->create(['example_sentences' => [
        ['sentence' => 'Removed.', 'image' => 'lessons/sentence-images/removed.png'],
        ['sentence' => 'Replaced.', 'image' => 'lessons/sentence-images/replaced.png'],
        ['sentence' => 'Kept.', 'image' => 'lessons/sentence-images/kept.png'],
    ]]);
    $undoRepeaterFake = Repeater::fake();

    Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
        ->fillForm(['example_sentences' => [
            ['sentence' => 'Replaced.', 'image' => [UploadedFile::fake()->image('new.png')]],
            ['sentence' => 'Kept.', 'image' => ['lessons/sentence-images/kept.png']],
        ]])
        ->call('save')
        ->assertHasNoFormErrors();

    $undoRepeaterFake();
    $images = array_column($lesson->fresh()->example_sentences, 'image');

    expect($images[1])->toBe('lessons/sentence-images/kept.png')
        ->and($images[0])->not->toBe('lessons/sentence-images/replaced.png');

    $disk->assertMissing(['lessons/sentence-images/removed.png', 'lessons/sentence-images/replaced.png']);
    $disk->assertExists(['lessons/sentence-images/kept.png', $images[0]]);
});

it('rejects an example sentence or meaning longer than 500 characters', function () {
    $undoRepeaterFake = Repeater::fake();

    Livewire::test(CreateLesson::class)
        ->fillForm(['unit_id' => Unit::factory()->create()->id, 'title' => 'Long', 'example_sentences' => [
            ['sentence' => 'Short.', 'translation' => str_repeat('a', 501)],
        ]])
        ->call('create')
        ->assertHasFormErrors(['example_sentences.0.translation' => 'max']);

    $undoRepeaterFake();
});

it('rejects a slug already used within the same unit', function () {
    $unit = Unit::factory()->create();
    Lesson::factory()->for($unit)->create(['slug' => 'hello']);

    Livewire::test(CreateLesson::class)
        ->fillForm(['unit_id' => $unit->id, 'title' => 'Hello', 'slug' => 'hello'])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'unique']);
});

it('allows the same slug in a different unit', function () {
    Lesson::factory()->for(Unit::factory())->create(['slug' => 'hello']);
    $otherUnit = Unit::factory()->create();

    Livewire::test(CreateLesson::class)
        ->fillForm(['unit_id' => $otherUnit->id, 'title' => 'Hello', 'slug' => 'hello'])
        ->call('create')
        ->assertHasNoFormErrors();
});

it('allows keeping the own slug when editing a lesson', function () {
    $lesson = Lesson::factory()->create(['slug' => 'hello']);

    Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
        ->fillForm(['title' => 'Hello again'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($lesson->refresh()->title)->toBe('Hello again');
});

it('labels unit options with the course name and searches by either name', function () {
    $unit = Unit::factory()->for(Course::factory()->create(['title' => 'Tiếng Anh lớp 3']))->create(['title' => 'Hello!']);
    Unit::factory()->for(Course::factory()->create(['title' => 'Ngữ pháp']))->create(['title' => 'Tenses']);

    $field = Livewire::test(CreateLesson::class)->instance()->getSchema('form')->getComponent('unit_id');

    expect($field->getSearchResults('lớp 3'))->toBe([$unit->id => 'Tiếng Anh lớp 3 » Hello!'])
        ->and($field->getSearchResults('Tenses'))->toHaveCount(1);
});

it('attaches flashcard sets to the lesson with a sort order', function () {
    $lesson = Lesson::factory()->create();
    [$first, $second] = FlashcardSet::factory()->count(2)->create();

    $manager = Livewire::test(FlashcardSetsRelationManager::class, ['ownerRecord' => $lesson, 'pageClass' => EditLesson::class]);

    foreach ([$first, $second] as $flashcardSet) {
        $manager->callAction(TestAction::make(AttachAction::class)->table(), ['recordId' => $flashcardSet->id])->assertHasNoFormErrors();
    }

    expect($lesson->flashcardSets()->pluck('flashcard_sets.id')->all())->toBe([$first->id, $second->id]);
});

it('attaches vocabularies to the lesson', function () {
    $lesson = Lesson::factory()->create();
    $vocabulary = Vocabulary::factory()->create();

    Livewire::test(VocabulariesRelationManager::class, ['ownerRecord' => $lesson, 'pageClass' => EditLesson::class])
        ->callAction(TestAction::make(AttachAction::class)->table(), ['recordId' => $vocabulary->id])
        ->assertHasNoFormErrors();

    expect($lesson->vocabularies()->pluck('vocabularies.id')->all())->toBe([$vocabulary->id]);
});

it('lets editors attach images to the passage and the content on the public disk', function (string $name) {
    $field = Livewire::test(CreateLesson::class)->instance()->getSchema('form')->getComponent($name);

    expect($field->hasFileAttachments())->toBeTrue()
        ->and($field->getFileAttachmentsDiskName())->toBe('public')
        ->and($field->getFileAttachmentsDirectory())->toBe('lessons/content')
        ->and($field->getFileAttachmentsVisibility())->toBe('public')
        ->and(collect($field->getToolbarButtons())->flatten())->toContain('attachFiles', 'h2', 'bulletList');
})->with(['passage', 'content']);

it('lays the form out in pairs: unit with title, slug with sort order, toggles together, passage audio and content', function () {
    $schema = Livewire::test(CreateLesson::class)->instance()->getSchema('form');
    $names = collect($schema->getComponents())->map(fn ($component) => $component->getName())->values()->all();

    expect($names)->toBe([
        'unit_id', 'title', 'slug', 'sort_order', 'is_free', 'is_published', 'description',
        'passage', 'video_source', 'video_url', 'passage_audio', 'example_sentences', 'content', 'attachments',
    ])
        ->and($schema->getComponent('passage_audio')->getColumnSpan('default'))->toBe('full')
        ->and($schema->getComponent('attachments')->getColumnSpan('default'))->toBe('full');
});

it('lists and reorders the attached vocabularies and flashcard sets of the lesson', function () {
    $lesson = Lesson::factory()->create();
    [$firstVocabulary, $secondVocabulary] = Vocabulary::factory()->count(2)->create();
    [$firstSet, $secondSet] = FlashcardSet::factory()->count(2)->create();
    $lesson->vocabularies()->attach([$firstVocabulary->id => ['sort_order' => 1], $secondVocabulary->id => ['sort_order' => 2]]);
    $lesson->flashcardSets()->attach([$firstSet->id => ['sort_order' => 1], $secondSet->id => ['sort_order' => 2]]);

    Livewire::test(VocabulariesRelationManager::class, ['ownerRecord' => $lesson, 'pageClass' => EditLesson::class])
        ->assertCanSeeTableRecords([$firstVocabulary, $secondVocabulary], inOrder: true)
        ->call('reorderTable', [$secondVocabulary->id, $firstVocabulary->id]);
    Livewire::test(FlashcardSetsRelationManager::class, ['ownerRecord' => $lesson, 'pageClass' => EditLesson::class])
        ->assertCanSeeTableRecords([$firstSet, $secondSet], inOrder: true)
        ->call('reorderTable', [$secondSet->id, $firstSet->id]);

    expect($lesson->vocabularies()->pluck('vocabularies.id')->all())->toBe([$secondVocabulary->id, $firstVocabulary->id])
        ->and($lesson->flashcardSets()->pluck('flashcard_sets.id')->all())->toBe([$secondSet->id, $firstSet->id]);
});

it('saves the lesson video as a YouTube link or as an uploaded file in the same field', function () {
    Storage::fake('public');
    $unit = Unit::factory()->create();

    Livewire::test(CreateLesson::class)
        ->fillForm(['unit_id' => $unit->id, 'title' => 'Link', 'sort_order' => 1, 'video_source' => 'youtube', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Lesson::firstWhere('title', 'Link'))
        ->video->toBe('https://youtu.be/dQw4w9WgXcQ')
        ->hasUploadedVideo()->toBeFalse();

    Livewire::test(CreateLesson::class)
        ->fillForm(['unit_id' => $unit->id, 'title' => 'Upload', 'sort_order' => 2, 'video_source' => 'upload', 'video_upload' => UploadedFile::fake()->create('clip.mp4', 100, 'video/mp4')])
        ->call('create')
        ->assertHasNoFormErrors();

    $lesson = Lesson::firstWhere('title', 'Upload');

    expect($lesson->hasUploadedVideo())->toBeTrue();
    Storage::disk('public')->assertExists($lesson->video);
});

it('shows the saved video in the right mode when editing, and rejects a link that is not YouTube', function () {
    $link = Lesson::factory()->create(['video' => 'https://youtu.be/dQw4w9WgXcQ']);
    $upload = Lesson::factory()->create(['video' => 'lessons/videos/clip.mp4']);

    Livewire::test(EditLesson::class, ['record' => $link->getRouteKey()])
        ->assertSchemaStateSet(['video_source' => 'youtube', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ'])
        ->fillForm(['video_url' => 'https://example.com/watch?v=dQw4w9WgXcQ'])
        ->call('save')
        ->assertHasFormErrors(['video_url']);

    Livewire::test(EditLesson::class, ['record' => $upload->getRouteKey()])
        ->assertSchemaStateSet(['video_source' => 'upload']);
});
