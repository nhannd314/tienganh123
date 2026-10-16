<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function richContent(string $text): string
{
    return '<p>'.$text.'</p>';
}

it('lists the published posts newest first and hides drafts and scheduled posts', function () {
    Post::factory()->published()->create(['title' => 'Older post', 'published_at' => now()->subDays(2)]);
    Post::factory()->published()->create(['title' => 'Newer post', 'published_at' => now()->subDay()]);
    Post::factory()->create(['title' => 'Draft post']);
    Post::factory()->published()->create(['title' => 'Scheduled post', 'published_at' => now()->addDay()]);

    $this->get(route('posts.index'))
        ->assertOk()
        ->assertSeeInOrder(['Newer post', 'Older post'])
        ->assertDontSee('Draft post')
        ->assertDontSee('Scheduled post');
});

it('filters the posts by category and paginates them', function () {
    $tips = Category::factory()->create(['name' => 'Mẹo học tập', 'slug' => 'meo-hoc-tap']);
    Post::factory()->published()->create(['title' => 'A tip', 'category_id' => $tips->id]);
    Post::factory()->published()->create(['title' => 'Uncategorised']);

    $this->get(route('posts.index', ['danh-muc' => 'meo-hoc-tap']))
        ->assertSee('A tip')
        ->assertDontSee('Uncategorised');

    Post::factory()->published()->count(12)->create();

    $this->get(route('posts.index'))->assertSee('aria-label="Phân trang"', false);
});

it('shows a post with its category, date, reading time, excerpt and content', function () {
    $category = Category::factory()->create(['name' => 'Mẹo học tập']);
    $post = Post::factory()->published()->create([
        'title' => '5 mẹo nhớ từ vựng',
        'excerpt' => 'Học từ vựng dễ hơn khi bé được nghe và nhìn.',
        'category_id' => $category->id,
        'published_at' => '2026-10-12 08:00:00',
        'content' => richContent('Nội dung chính của bài'),
    ]);

    $this->get(route('posts.show', $post))
        ->assertOk()
        ->assertSeeInOrder(['<h1', '5 mẹo nhớ từ vựng', 'Mẹo học tập', '12/10/2026', '1 phút đọc', 'Học từ vựng dễ hơn', 'Nội dung chính của bài'], false)
        ->assertSee('Sao chép link');
});

it('does not show a draft or scheduled post', function () {
    $this->get(route('posts.show', Post::factory()->create()))->assertNotFound();
    $this->get(route('posts.show', Post::factory()->published()->create(['published_at' => now()->addDay()])))->assertNotFound();
});

it('lists the related posts, those of the same category first, without the post itself', function () {
    $category = Category::factory()->create();
    $post = Post::factory()->published()->create(['title' => 'Main post', 'category_id' => $category->id]);
    Post::factory()->published()->create(['title' => 'Newest elsewhere', 'published_at' => now()->subHour()]);
    Post::factory()->published()->create(['title' => 'Same category', 'category_id' => $category->id, 'published_at' => now()->subDays(5)]);

    $this->get(route('posts.show', $post))
        ->assertSee('Bài viết liên quan')
        ->assertSeeInOrder(['Same category', 'Newest elsewhere']);
});

it('suggests the latest courses and does not invite to a free trial', function () {
    Course::factory()->published()->create(['title' => 'Free course', 'monthly_price' => 0]);
    Course::factory()->published()->priced()->create(['title' => 'Paid course']);
    $post = Post::factory()->published()->create();

    $this->get(route('posts.show', $post))
        ->assertDontSee('Học thử miễn phí')
        ->assertSee('Khóa học dành cho bạn')
        ->assertSee('Paid course');
});

it('shows a published fixed page with the other pages in the sidebar', function () {
    $page = Page::factory()->published()->create(['title' => 'Câu hỏi thường gặp', 'content' => richContent('Hỏi đáp ở đây')]);
    Page::factory()->published()->create(['title' => 'Dành cho phụ huynh']);
    Page::factory()->create(['title' => 'Trang nháp']);

    $this->get(route('pages.show', $page))
        ->assertOk()
        ->assertSeeInOrder(['Câu hỏi thường gặp', 'Hỏi đáp ở đây', 'Xem thêm', 'Dành cho phụ huynh'])
        ->assertDontSee('Trang nháp');
});

it('does not show an unpublished page', function () {
    $this->get(route('pages.show', Page::factory()->create()))->assertNotFound();
});

it('links the published fixed pages from the footer', function () {
    $page = Page::factory()->published()->create(['title' => 'Hướng dẫn học']);

    $this->get(route('posts.index'))->assertSee(route('pages.show', $page), false)->assertSee('Hướng dẫn học');
});

it('always shows the related posts widget, with a note when there are none', function () {
    $post = Post::factory()->published()->create();

    $this->get(route('posts.show', $post))
        ->assertSee('Bài viết liên quan')
        ->assertSee('Chưa có bài viết liên quan.');
});

it('calls the posts section the English blog, under its own address, in the menu and the footer', function () {
    $post = Post::factory()->published()->create();

    expect(route('posts.index', absolute: false))->toBe('/blog-tieng-anh')
        ->and(route('posts.show', $post, absolute: false))->toBe('/blog-tieng-anh/'.$post->slug);

    $this->get('/blog-tieng-anh')
        ->assertOk()
        ->assertSee('Blog tiếng Anh')
        ->assertSee('href="'.route('posts.index').'"', false);
    $this->get('/bai-viet')->assertNotFound();
});

it('shows the contact details in the footer', function () {
    $this->get(route('posts.index'))
        ->assertSee('href="mailto:nhannd314@gmail.com"', false)
        ->assertSee('href="tel:0965991099"', false)
        ->assertSee('0965 991 099')
        ->assertSee('href="https://facebook.com/nhannd314"', false)
        ->assertSee('facebook.com/nhannd314');
});

it('seeds the three fixed pages with their contact details, without overwriting a page that exists', function () {
    Page::factory()->published()->create(['slug' => 'huong-dan-hoc', 'title' => 'Hướng dẫn học', 'content' => '<p>Đã sửa</p>']);

    $this->seed(PageSeeder::class);
    $this->seed(PageSeeder::class);

    expect(Page::query()->orderBy('sort_order')->pluck('slug')->all())->toEqualCanonicalizing(['huong-dan-hoc', 'cau-hoi-thuong-gap', 'danh-cho-phu-huynh'])
        ->and(Page::where('slug', 'huong-dan-hoc')->value('content'))->toBe('<p>Đã sửa</p>');

    $this->get(route('pages.show', Page::where('slug', 'danh-cho-phu-huynh')->first()))
        ->assertOk()
        ->assertSee('Dành cho phụ huynh')
        ->assertSee('nhannd314@gmail.com')
        ->assertSee('0965 991 099');
    $this->get(route('pages.show', Page::where('slug', 'cau-hoi-thuong-gap')->first()))->assertOk()->assertSee('Câu hỏi thường gặp');
    $this->get(route('posts.index'))
        ->assertSee(route('pages.show', Page::where('slug', 'huong-dan-hoc')->first()), false)
        ->assertSee(route('pages.show', Page::where('slug', 'cau-hoi-thuong-gap')->first()), false)
        ->assertSee(route('pages.show', Page::where('slug', 'danh-cho-phu-huynh')->first()), false);
});

it('seeds the privacy policy and terms of service in both languages, listed in the footer, without overwriting a page that exists', function () {
    Page::factory()->published()->create(['slug' => 'dieu-khoan-su-dung', 'title' => 'Điều khoản sử dụng', 'content' => '<p>Đã sửa</p>']);

    $this->seed(LegalPageSeeder::class);
    $this->seed(LegalPageSeeder::class);

    expect(Page::query()->pluck('slug')->all())->toEqualCanonicalizing(['chinh-sach-bao-mat', 'dieu-khoan-su-dung'])
        ->and(Page::where('slug', 'dieu-khoan-su-dung')->value('content'))->toBe('<p>Đã sửa</p>');

    $privacyPolicy = Page::where('slug', 'chinh-sach-bao-mat')->first();

    $this->get(route('pages.show', $privacyPolicy))
        ->assertOk()
        ->assertSee('Chính sách bảo mật')
        ->assertSee('Privacy Policy')
        ->assertSee('nhannd314@gmail.com');
    $this->get(route('home'))->assertSeeInOrder(['aria-label="Pháp lý"', route('pages.show', $privacyPolicy)], false);
});
