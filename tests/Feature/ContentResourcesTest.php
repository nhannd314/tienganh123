<?php

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('generates the slug of a category from its name', function () {
    Livewire::test(CreateCategory::class)
        ->fillForm(['name' => 'Mẹo học tiếng Anh', 'sort_order' => 1])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Category::firstWhere('name', 'Mẹo học tiếng Anh'))->slug->toBe('meo-hoc-tieng-anh');
});

it('creates an unpublished post in a category and generates its slug', function () {
    $category = Category::factory()->create();

    Livewire::test(CreatePost::class)
        ->fillForm(['title' => 'Cách học từ vựng', 'category_id' => $category->id, 'content' => '<p>Nội dung</p>'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Post::firstWhere('title', 'Cách học từ vựng'))
        ->slug->toBe('cach-hoc-tu-vung')
        ->is_published->toBeFalse()
        ->category->is($category)->toBeTrue();
});

it('rejects a post slug already used by another post', function () {
    Post::factory()->create(['slug' => 'bai-viet']);

    Livewire::test(CreatePost::class)
        ->fillForm(['title' => 'Bài viết', 'slug' => 'bai-viet'])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'unique']);
});

it('creates a fixed page and generates its slug', function () {
    Livewire::test(CreatePage::class)
        ->fillForm(['title' => 'Câu hỏi thường gặp', 'sort_order' => 2, 'content' => '<p>Hỏi đáp</p>'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Page::firstWhere('title', 'Câu hỏi thường gặp'))
        ->slug->toBe('cau-hoi-thuong-gap')
        ->is_published->toBeFalse();
});

it('only counts a post as published once it is switched on and its date has come', function () {
    Post::factory()->published()->create(['title' => 'Visible']);
    Post::factory()->published()->create(['title' => 'No date', 'published_at' => null]);
    Post::factory()->published()->create(['title' => 'Scheduled', 'published_at' => now()->addDay()]);
    Post::factory()->create(['title' => 'Draft']);

    expect(Post::published()->pluck('title')->sort()->values()->all())->toBe(['No date', 'Visible']);
});

it('only counts a page as published when it is switched on', function () {
    Page::factory()->published()->create(['title' => 'Live']);
    Page::factory()->create(['title' => 'Draft']);

    expect(Page::published()->pluck('title')->all())->toBe(['Live']);
});

it('keeps the posts when their category is deleted', function () {
    $category = Category::factory()->create();
    $post = Post::factory()->create(['category_id' => $category->id]);

    $category->delete();

    expect($post->fresh()->category_id)->toBeNull();
});
