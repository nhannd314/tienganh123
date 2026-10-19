<?php

use App\Filament\Resources\Lessons\Pages\CreateLesson;
use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Filament\Resources\Lessons\Pages\ListLessons;
use App\Filament\Resources\Lessons\RelationManagers\FlashcardSetsRelationManager;
use App\Filament\Resources\Lessons\RelationManagers\VocabulariesRelationManager;
use App\Models\Course;
use App\Models\FlashcardSet;
use App\Models\Lesson;
use App\Models\SentenceSet;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vocabulary;
use Filament\Actions\AttachAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        'passage', 'passage_audio', 'sentence_set_id', 'content', 'attachments',
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

it('links the lesson to a sentence set, which is optional', function () {
    $set = SentenceSet::factory()->create();
    $unit = Unit::factory()->create();

    Livewire::test(CreateLesson::class)
        ->fillForm(['unit_id' => $unit->id, 'title' => 'With sentences', 'sentence_set_id' => $set->id])
        ->call('create')
        ->assertHasNoFormErrors();

    Livewire::test(CreateLesson::class)
        ->fillForm(['unit_id' => $unit->id, 'title' => 'Without sentences'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Lesson::firstWhere('title', 'With sentences')->sentenceSet->is($set))->toBeTrue()
        ->and(Lesson::firstWhere('title', 'Without sentences')->sentence_set_id)->toBeNull();
});

it('lists lessons from the newest to the oldest and filters them by course', function () {
    $this->actingAs(User::factory()->create());
    $course = Course::factory()->create();
    $otherCourse = Course::factory()->create();
    $old = Lesson::factory()->for(Unit::factory()->for($course))->create(['created_at' => now()->subDay()]);
    $new = Lesson::factory()->for(Unit::factory()->for($course))->create(['created_at' => now()]);
    $elsewhere = Lesson::factory()->for(Unit::factory()->for($otherCourse))->create(['created_at' => now()->subWeek()]);

    Livewire::test(ListLessons::class)
        ->assertCanSeeTableRecords([$new, $old, $elsewhere], inOrder: true)
        ->filterTable('course', $course->id)
        ->assertCanSeeTableRecords([$new, $old], inOrder: true)
        ->assertCanNotSeeTableRecords([$elsewhere]);
});
