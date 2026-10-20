<?php

use App\Models\User;
use App\Models\Vocabulary;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function reviewVocabulary(int $wordCount = 3): Vocabulary
{
    $vocabulary = Vocabulary::factory()->create(['title' => 'Fruits']);

    Word::factory()->count($wordCount)->create()
        ->each(fn (Word $word, int $index) => $vocabulary->words()->attach($word, ['sort_order' => $index + 1]));

    return $vocabulary;
}

/**
 * @return array<string, mixed>
 */
function vocabularyReviewConfig(string $html): array
{
    preg_match('/<script type="application\/json" data-review-config>(.*?)<\/script>/s', $html, $match);

    return json_decode($match[1], true);
}

it('sends a guest to the login page', function () {
    $this->get(route('vocabularies.review', [reviewVocabulary(), 1]))->assertRedirect(route('login'));
});

it('tells a signed-in user without a running membership that the review is for members', function (User $user) {
    $vocabulary = reviewVocabulary();

    $this->actingAs($user)
        ->get(route('vocabularies.review', [$vocabulary, 1]))
        ->assertForbidden()
        ->assertSee('Ôn tập từ vựng dành cho thành viên')
        ->assertSee(route('vocabularies.show', $vocabulary), false)
        ->assertDontSee('data-review-config', false);
})->with([
    'no membership' => fn () => User::factory()->create(),
    'expired membership' => fn () => User::factory()->member(today()->subDay()->toDateString())->create(),
]);

it('asks a member about every word of the vocabulary that has a meaning, without saving the answers', function () {
    $vocabulary = reviewVocabulary(12);
    $vocabulary->words()->attach(Word::factory()->create(['meanings' => []]), ['sort_order' => 13]);

    $response = $this->actingAs(User::factory()->member()->create())
        ->get(route('vocabularies.review', [$vocabulary, 1]))
        ->assertOk()
        ->assertSeeInOrder(['Trang chủ', 'Từ vựng', 'Fruits', 'Ôn tập'])
        ->assertSee('Chọn nghĩa đúng cho mỗi từ nhé!')
        ->assertSeeInOrder(['Trả lời nghĩa', 'Trả lời từ', 'Nghe và trả lời nghĩa'])
        ->assertDontSee('Điền từ tiếng Anh')
        ->assertSee(route('vocabularies.review', [$vocabulary, 2]), false);

    $config = vocabularyReviewConfig($response->getContent());

    expect($config['questions'])->toHaveCount(12)
        ->and($config['canSave'])->toBeFalse()
        ->and($config['submitUrl'])->toBeNull()
        ->and($config['mode'])->toBe('choice');
});

it('shows the sidebar of the vocabulary page beside the review, without the review card', function () {
    $this->actingAs(User::factory()->member()->create())
        ->get(route('vocabularies.review', [reviewVocabulary(), 1]))
        ->assertSeeInOrder(['data-review-config', '<aside', 'Khóa học liên quan', 'Từ vựng cùng chủ đề', 'Từ vựng liên quan', '</aside>'], false)
        ->assertDontSee('Ôn tập nhanh ở từ vựng');
});

it('opens the review to an admin', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('vocabularies.review', [reviewVocabulary(), 2]))
        ->assertOk()
        ->assertSee('Chọn từ tiếng Anh đúng nhé!');
});

it('ends the review with part 3, which plays the word without showing it', function () {
    $vocabulary = reviewVocabulary();

    $response = $this->actingAs(User::factory()->member()->create())
        ->get(route('vocabularies.review', [$vocabulary, 3]))
        ->assertOk()
        ->assertDontSee('Phần tiếp theo');

    expect(collect(vocabularyReviewConfig($response->getContent())['questions'])->pluck('phonetic')->filter())->toBeEmpty();
});

it('has no part past part 3', function () {
    $this->actingAs(User::factory()->member()->create())
        ->get('/tu-vung/'.reviewVocabulary()->slug.'/on-tap/4')
        ->assertNotFound();
});

it('links the vocabulary page to its review at the bottom of the sidebar, only when a word has a meaning', function () {
    $vocabulary = reviewVocabulary();
    $empty = Vocabulary::factory()->create();

    $this->get(route('vocabularies.show', $vocabulary))
        ->assertSeeInOrder(['<aside', 'Từ vựng liên quan', 'Ôn tập từ vựng', 'Ôn tập nhanh ở từ vựng không lưu kết quả', route('vocabularies.review', [$vocabulary, 1]), 'Ôn tập nhanh', '</aside>'], false);
    $this->get(route('vocabularies.show', $empty))->assertDontSee('Ôn tập từ vựng');
});
