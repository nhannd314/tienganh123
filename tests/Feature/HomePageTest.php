<?php

use App\Models\Course;
use App\Models\Flashcard;
use App\Models\Lesson;
use App\Models\Unit;
use App\Models\User;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('lists the published courses by sort order, then the newest first', function () {
    Course::factory()->published()->create(['title' => 'Last Course', 'sort_order' => 2, 'created_at' => now()->addDay()]);
    Course::factory()->published()->create(['title' => 'Older Course', 'sort_order' => 1, 'created_at' => now()->subDay()]);
    Course::factory()->published()->create(['title' => 'Newer Course', 'sort_order' => 1, 'created_at' => now()]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSeeInOrder(['Danh sách khóa học', 'Newer Course', 'Older Course', 'Last Course']);
});

it('shows only the 20 newest courses and links to the full list', function () {
    Course::factory()->published()->count(21)->sequence(fn ($sequence) => [
        'title' => 'Course '.str_pad($sequence->index + 1, 2, '0', STR_PAD_LEFT),
        'created_at' => now()->subMinutes(30 - $sequence->index),
    ])->create();

    $this->get(route('home'))
        ->assertSee('Course 21')
        ->assertSee('Course 02')
        ->assertDontSee('Course 01')
        ->assertSee(route('courses.index'), false);
});

it('hides unpublished courses', function () {
    Course::factory()->published()->create(['title' => 'Visible Course']);
    Course::factory()->create(['title' => 'Draft Course']);

    $this->get(route('home'))
        ->assertSee('Visible Course')
        ->assertDontSee('Draft Course');
});

it('shows an empty state when there are no published courses', function () {
    Course::factory()->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Chưa có khóa học nào');
});

it('renders the hero, features and footer', function () {
    $this->get(route('home'))
        ->assertSee('bằng tất cả niềm vui')
        ->assertSeeInOrder(['Học từ vựng', 'Thực hành nhiều', 'Chơi và ghi nhớ'])
        ->assertSee('Vì sao bạn sẽ thích FunEnglish')
        ->assertSee('Học tiếng Anh như này mà còn không học được nữa thì thôi!');
});

it('ranks the learners in the leaderboard by their total score over all the published lessons', function () {
    $course = Course::factory()->published()->create();
    $unit = Unit::factory()->for($course)->create();
    $lessons = Lesson::factory()->published()->for($unit)->count(2)->create();
    $otherCourse = Course::factory()->published()->create();
    $otherLesson = Lesson::factory()->published()->for(Unit::factory()->for($otherCourse))->create();
    $draftLesson = Lesson::factory()->for($unit)->create();

    $top = User::factory()->create(['name' => 'Học Sinh Giỏi']);
    $runnerUp = User::factory()->create(['name' => 'Học Sinh Khá']);

    $lessons[0]->scores()->create(['user_id' => $top->id, 'score' => 40, 'percent' => 100]);
    $otherLesson->scores()->create(['user_id' => $top->id, 'score' => 1200, 'percent' => 100]);
    $lessons[1]->scores()->create(['user_id' => $runnerUp->id, 'score' => 60, 'percent' => 100]);
    $draftLesson->scores()->create(['user_id' => $runnerUp->id, 'score' => 9000, 'percent' => 100]);

    $this->get(route('home'))
        ->assertSee('id="leaderboard" hidden', false)
        ->assertSee('Top 10 học sinh điểm cao nhất')
        ->assertSeeInOrder(['Nhà vua', 'Học Sinh Giỏi', '1.240', 'Thái sư', 'Học Sinh Khá', '60'])
        ->assertDontSee('9.000');
});

it('shows an empty leaderboard when nobody has scored', function () {
    $this->get(route('home'))->assertSee('Chưa có học sinh nào ghi điểm.');
});

it('shows 4 random flashcards with a picture as buttons that play their audio', function () {
    Flashcard::factory()->count(7)->create(['image' => 'flashcards/images/card.png', 'audio' => 'flashcards/audio/card.mp3']);
    Flashcard::factory()->create(['title' => 'No Picture Card', 'image' => null]);

    $html = $this->get(route('home'))->getContent();

    expect(substr_count($html, 'data-flashcard-image'))->toBe(4)
        ->and($html)->toContain('data-src="'.Storage::disk('public')->url('flashcards/audio/card.mp3').'"')
        ->and($html)->not->toContain('No Picture Card');
});

it('speaks a flashcard without audio and uses the picture of its word', function () {
    $word = Word::factory()->create(['word' => 'giraffe', 'image' => 'words/giraffe.png']);
    Flashcard::factory()->create(['title' => null, 'word_id' => $word->id, 'image' => null, 'audio' => null]);

    $this->get(route('home'))
        ->assertSee('data-text="giraffe"', false)
        ->assertSee(Storage::disk('public')->url('words/giraffe.png'), false)
        ->assertDontSee('data-src', false);
});

it('hides the flashcards when there are none', function () {
    $this->get(route('home'))->assertDontSee('data-flashcard-image', false);
});

it('shows a floating Messenger chat button linking to the Facebook page of the site', function () {
    $this->get(route('home'))
        ->assertSee('href="https://m.me/nhannd314"', false)
        ->assertSee('aria-label="Chat Messenger"', false);
});
