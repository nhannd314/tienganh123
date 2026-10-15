<?php

use App\Models\Course;
use App\Models\Flashcard;
use App\Models\FlashcardSet;
use App\Models\Vocabulary;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('finds published courses, vocabularies and flashcard sets by their title', function () {
    Course::factory()->published()->create(['title' => 'Animal Course']);
    Course::factory()->create(['title' => 'Animal Draft']);
    Course::factory()->published()->create(['title' => 'Car Course']);
    Vocabulary::factory()->create(['title' => 'Animal words']);
    FlashcardSet::factory()->create(['title' => 'Animal cards']);

    $this->get(route('search', ['q' => 'animal']))
        ->assertOk()
        ->assertSee('Animal Course')
        ->assertSee('Animal words')
        ->assertSee('Animal cards')
        ->assertDontSee('Animal Draft')
        ->assertDontSee('Car Course');
});

it('finds a vocabulary by one of its words and a flashcard set by one of its cards', function () {
    Vocabulary::factory()->create(['title' => 'Farm'])->words()->attach(Word::factory()->create(['word' => 'elephant'])->id, ['sort_order' => 0]);
    FlashcardSet::factory()->create(['title' => 'Zoo'])->flashcards()->attach(Flashcard::factory()->create(['title' => 'elephant'])->id, ['sort_order' => 0]);

    $this->get(route('search', ['q' => 'elephant']))->assertSee('Farm')->assertSee('Zoo');
});

it('limits the results to one kind with the loai parameter', function () {
    Course::factory()->published()->create(['title' => 'Animal Course']);
    Vocabulary::factory()->create(['title' => 'Animal words']);

    $this->get(route('search', ['q' => 'animal', 'loai' => 'tu-vung']))
        ->assertSee('Animal words')
        ->assertDontSee('Animal Course');
});

it('treats like wildcards in the term literally', function () {
    Vocabulary::factory()->create(['title' => 'Fruits']);

    $this->get(route('search', ['q' => '%']))->assertSee('Không tìm thấy kết quả nào');
});

it('asks for a term when none is given', function () {
    $this->get(route('search'))->assertOk()->assertSee('Nhập từ khóa để tìm kiếm');
});

it('submits the header search form to the search page', function () {
    $this->get(route('home'))->assertSee('action="'.route('search').'"', false);
});

it('finds results regardless of accents and case, including "đ"', function () {
    Vocabulary::factory()->create(['title' => 'Động vật']);
    Course::factory()->published()->create(['title' => 'Tiếng Anh lớp 3']);

    $this->get(route('search', ['q' => 'dong VAT']))->assertSee('Động vật');
    $this->get(route('search', ['q' => 'tieng anh lop 3']))->assertSee('Tiếng Anh lớp 3');
    $this->get(route('search', ['q' => 'Động']))->assertSee('Động vật');
});

it('paginates the results of one kind and keeps the term in the page links', function () {
    Vocabulary::factory()->count(13)->sequence(fn ($sequence) => [
        'title' => 'Animal '.str_pad($sequence->index + 1, 2, '0', STR_PAD_LEFT),
        'sort_order' => $sequence->index + 1,
    ])->create();

    $this->get(route('search', ['q' => 'animal', 'loai' => 'tu-vung']))
        ->assertSee('Animal 12')->assertDontSee('Animal 13')
        ->assertSee('aria-label="Phân trang"', false)
        ->assertSee('q=animal', false);

    $this->get(route('search', ['q' => 'animal', 'loai' => 'tu-vung', 'page' => 2]))
        ->assertSee('Animal 13')->assertDontSee('Animal 01');
});

it('previews 6 results per kind and links to all of them', function () {
    Vocabulary::factory()->count(7)->sequence(fn ($sequence) => [
        'title' => 'Animal '.($sequence->index + 1),
        'sort_order' => $sequence->index + 1,
    ])->create();

    $this->get(route('search', ['q' => 'animal']))
        ->assertSee('Animal 6')->assertDontSee('Animal 7')
        ->assertSee('Xem tất cả');
});
