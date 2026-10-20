<?php

use App\Models\Sentence;
use App\Models\SentenceSet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param  list<array<string, mixed>>  $sentences
 */
function reviewSentenceSet(array $sentences): SentenceSet
{
    $set = SentenceSet::factory()->create(['title' => 'Bộ câu về trái cây']);

    foreach ($sentences as $index => $sentence) {
        Sentence::factory()->hasAttached($set, ['sort_order' => $index + 1], 'sentenceSets')->create(['translation' => null, ...$sentence]);
    }

    return $set;
}

/**
 * @return array<string, mixed>
 */
function sentenceReviewConfig(string $html): array
{
    preg_match('/<script type="application\/json" data-review-config>(.*?)<\/script>/s', $html, $match);

    return json_decode($match[1], true);
}

it('sends a guest to the login page', function () {
    $this->get(route('sentences.review', [reviewSentenceSet([['sentence' => 'Water is cold.']]), 9]))->assertRedirect(route('login'));
});

it('tells a signed-in user without a running membership that the review is for members', function () {
    $set = reviewSentenceSet([['sentence' => 'Water is cold.']]);

    $this->actingAs(User::factory()->create())
        ->get(route('sentences.review', [$set, 9]))
        ->assertForbidden()
        ->assertSee('Ôn tập câu mẫu dành cho thành viên')
        ->assertSee(route('sentences.show', $set), false)
        ->assertDontSee('data-review-config', false);
});

it('asks a member to fill in the marked words of every sentence that has some, without saving the answers', function () {
    $set = reviewSentenceSet(array_map(fn (int $number): array => ['sentence' => "I eat `apple` {$number}.", 'translation' => "Tôi ăn táo {$number}."], range(1, 12)));

    $response = $this->actingAs(User::factory()->member()->create())
        ->get(route('sentences.review', [$set, 7]))
        ->assertOk()
        ->assertSeeInOrder(['Trang chủ', 'Câu mẫu', 'Bộ câu về trái cây', 'Ôn tập'])
        ->assertSee('Điền từ còn thiếu vào câu nhé!')
        ->assertSeeInOrder(['Điền câu', 'Viết câu', 'Nghe câu'])
        ->assertSee(route('sentences.review', [$set, 8]), false);

    $config = sentenceReviewConfig($response->getContent());

    expect($config['questions'])->toHaveCount(12)
        ->and($config['canSave'])->toBeFalse()
        ->and($config['submitUrl'])->toBeNull()
        ->and($config['mode'])->toBe('sentence');
});

it('asks the sentences that have a meaning in part 8 and every sentence in part 9', function () {
    $set = reviewSentenceSet([
        ['sentence' => 'I eat an `apple`.', 'translation' => 'Tôi ăn táo.'],
        ['sentence' => 'Water is cold.'],
    ]);
    $member = User::factory()->member()->create();

    $writing = sentenceReviewConfig($this->actingAs($member)->get(route('sentences.review', [$set, 8]))->assertOk()->getContent());
    $dictation = sentenceReviewConfig($this->actingAs($member)->get(route('sentences.review', [$set, 9]))->assertOk()->assertDontSee('Phần tiếp theo')->getContent());

    expect(collect($writing['questions'])->pluck('word')->all())->toBe(['I eat an apple.'])
        ->and($writing['mode'])->toBe('write')
        ->and(collect($dictation['questions'])->pluck('word')->all())->toBe(['I eat an apple.', 'Water is cold.'])
        ->and($dictation['autoListen'])->toBeTrue();
});

it('leaves out the parts the set has no sentences for and sends them to the first part it has', function () {
    $set = reviewSentenceSet([['sentence' => 'Water is cold.']]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('sentences.review', [$set, 7]))
        ->assertRedirect(route('sentences.review', [$set, 9]));

    $this->get(route('sentences.review', [$set, 9]))
        ->assertOk()
        ->assertSee('Nghe câu')
        ->assertDontSee('Điền câu')
        ->assertDontSee('Viết câu');
});

it('shows the sidebar of the sentence set page beside the review, without the review card', function () {
    $this->actingAs(User::factory()->member()->create())
        ->get(route('sentences.review', [reviewSentenceSet([['sentence' => 'Water is cold.']]), 9]))
        ->assertSeeInOrder(['data-review-config', '<aside', 'Khóa học liên quan', 'Bộ từ vựng liên quan', 'Flashcard liên quan', 'Bộ câu cùng chủ đề', '</aside>'], false)
        ->assertDontSee('Ôn tập nhanh ở câu mẫu');
});

it('has no part outside parts 7 to 9', function () {
    $this->actingAs(User::factory()->member()->create())
        ->get('/cau-mau/'.reviewSentenceSet([['sentence' => 'Water is cold.']])->slug.'/on-tap/1')
        ->assertNotFound();
});

it('links the sentence set page to its review at the bottom of the sidebar', function () {
    $set = reviewSentenceSet([['sentence' => 'Water is cold.']]);

    $this->get(route('sentences.show', $set))
        ->assertSeeInOrder(['<aside', 'Bộ câu cùng chủ đề', 'Ôn tập câu mẫu', 'Ôn tập nhanh ở câu mẫu không lưu kết quả', route('sentences.review', [$set, 7]), 'Ôn tập nhanh', '</aside>'], false);
});
