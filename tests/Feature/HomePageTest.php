<?php

use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists the newest published courses first', function () {
    Course::factory()->published()->create(['title' => 'Older Course', 'created_at' => now()->subDay()]);
    Course::factory()->published()->create(['title' => 'Newer Course', 'created_at' => now()]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSeeInOrder(['Danh sách khóa học', 'Newer Course', 'Older Course']);
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
        ->assertSee('Learn • Play • Grow');
});

it('renders the leaderboard drawer closed with ranked learners', function () {
    $this->get(route('home'))
        ->assertSee('id="leaderboard" hidden', false)
        ->assertSee('Top 10 học sinh điểm cao nhất')
        ->assertSeeInOrder(['Nhà vua', 'Nguyễn Minh Anh', '1.280', 'Thái sư', 'Trần Gia Bảo']);
});
