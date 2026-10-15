<?php

use App\Models\Tag;
use App\Models\Vocabulary;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists the vocabularies with their tags, word count, the first 3 words and the rest as +N', function () {
    $vocabulary = Vocabulary::factory()->create(['title' => 'Fruits']);
    $vocabulary->tags()->attach(Tag::factory()->create(['name' => 'Grade 1']));
    $vocabulary->words()->attach(
        collect(['apple', 'banana', 'cherry', 'date', 'elderberry'])
            ->mapWithKeys(fn (string $text, int $index) => [Word::factory()->create(['word' => $text])->id => ['sort_order' => $index]])
            ->all()
    );

    $this->get(route('vocabularies.index'))
        ->assertOk()
        ->assertSeeInOrder(['Fruits', '5 từ', 'Grade 1', 'apple', 'banana', 'cherry', '+2'])
        ->assertDontSee('date')
        ->assertDontSee('elderberry');
});

it('shows no +N when the vocabulary has at most 3 words', function () {
    $vocabulary = Vocabulary::factory()->create();
    $vocabulary->words()->attach(Word::factory()->count(3)->create()->pluck('id')->mapWithKeys(fn (int $id) => [$id => ['sort_order' => $id]])->all());

    $this->get(route('vocabularies.index'))->assertSee('3 từ')->assertDontSee('+0');
});

it('filters by tag and lists only tags that have vocabularies', function () {
    $fruits = Tag::factory()->create(['name' => 'Fruits tag', 'slug' => 'fruits']);
    Tag::factory()->create(['name' => 'Unused tag']);
    Vocabulary::factory()->create(['title' => 'Apples'])->tags()->attach($fruits);
    Vocabulary::factory()->create(['title' => 'Cars']);

    $this->get(route('vocabularies.index'))
        ->assertSee('Apples')->assertSee('Cars')->assertSee('Fruits tag')->assertDontSee('Unused tag');

    $this->get(route('vocabularies.index', ['tag' => 'fruits']))
        ->assertSee('Apples')->assertDontSee('Cars');
});

it('paginates the vocabularies', function () {
    Vocabulary::factory()->count(13)->sequence(fn ($sequence) => [
        'title' => 'Vocab '.str_pad($sequence->index + 1, 2, '0', STR_PAD_LEFT),
        'sort_order' => $sequence->index + 1,
    ])->create();

    $this->get(route('vocabularies.index'))
        ->assertSee('Vocab 12')->assertDontSee('Vocab 13')
        ->assertSee('aria-label="Phân trang"', false);

    $this->get(route('vocabularies.index', ['page' => 2]))->assertSee('Vocab 13')->assertDontSee('Vocab 01');
});

it('removes the notification bell from the header', function () {
    $this->get(route('vocabularies.index'))->assertDontSee('Thông báo')->assertDontSee('icon-bell', false);
});
