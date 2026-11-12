<?php

use App\Models\Slide;
use App\Models\SlideDeck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

it('sends a guest to sign in and a user without the membership to buy it', function () {
    $this->get(route('slides.index'))->assertRedirect(route('login'));
    $this->post(route('slides.store'))->assertUnauthorized();

    $this->actingAs(User::factory()->create())->get(route('slides.index'))->assertRedirect(route('membership.enroll'));
    $this->post(route('slides.store'), ['title' => 'x'])->assertRedirect(route('membership.enroll'));

    expect(SlideDeck::count())->toBe(0);
});

it('shows a member the form and the slideshows they made', function () {
    $member = User::factory()->member()->create();
    SlideDeck::factory()->for($member)->create(['title' => 'Bộ slide của tôi', 'code' => 'abc1234']);
    SlideDeck::factory()->create(['title' => 'Bộ slide của người khác']);

    $this->actingAs($member)->get(route('slides.index'))
        ->assertOk()
        ->assertSee('Tạo slide trình chiếu')
        ->assertSee('Bộ slide của tôi')
        ->assertSee(route('slides.show', 'abc1234'), false)
        ->assertDontSee('Bộ slide của người khác');
});

it('lets an admin in too', function () {
    $this->actingAs(User::factory()->admin()->create())->get(route('slides.index'))->assertOk();
});

it('saves the slides in order and gives a short link', function () {
    $member = User::factory()->member()->create();

    $this->actingAs($member)->post(route('slides.store'), [
        'title' => 'Gia đình',
        'slides' => [
            3 => ['image' => UploadedFile::fake()->image('a.jpg'), 'text' => 'Mother'],
            7 => ['image' => UploadedFile::fake()->image('b.png'), 'text' => ''],
        ],
    ])->assertRedirect(route('slides.index'));

    $deck = SlideDeck::sole();

    expect($deck->user_id)->toBe($member->id)
        ->and($deck->code)->toMatch('/^[a-z0-9]{7}$/')
        ->and($deck->slides->pluck('position')->all())->toBe([1, 2])
        ->and($deck->slides->pluck('text')->all())->toBe(['Mother', null]);

    Storage::disk('public')->assertExists($deck->slides->pluck('image')->all());
});

it('rejects a deck without slides, with too many slides, a slide without an image and a file that is not an image', function (array $data) {
    $this->actingAs(User::factory()->member()->create())
        ->post(route('slides.store'), ['title' => 'Gia đình', ...$data])
        ->assertSessionHasErrors();

    expect(SlideDeck::count())->toBe(0);
})->with([
    'no slides' => [[]],
    'more than 20 slides' => fn (): array => [['slides' => array_map(fn (int $index): array => ['image' => UploadedFile::fake()->image("{$index}.jpg")], range(1, 21))]],
    'no image' => [['slides' => [['text' => 'Mother']]]],
    'not an image' => [['slides' => [['image' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')]]]],
]);

it('shows the slideshow full screen to anyone with the link', function () {
    $deck = SlideDeck::factory()->create(['title' => 'Gia đình', 'code' => 'abc1234']);
    Slide::factory()->for($deck, 'deck')->create(['position' => 2, 'text' => 'Second slide']);
    Slide::factory()->for($deck, 'deck')->create(['position' => 1, 'text' => 'First slide']);

    $this->get('/s/abc1234')
        ->assertOk()
        ->assertSeeInOrder(['First slide', 'Second slide'])
        ->assertSee('data-slide-presenter', false);

    $this->get('/s/nosuchcode')->assertNotFound();
});

it('lets only the maker delete a slideshow, with its images', function () {
    $deck = SlideDeck::factory()->create();
    $slide = Slide::factory()->for($deck, 'deck')->create(['image' => 'slides/one.jpg']);
    Storage::disk('public')->put('slides/one.jpg', 'x');

    $this->actingAs(User::factory()->member()->create())->delete(route('slides.destroy', $deck))->assertForbidden();
    expect(SlideDeck::count())->toBe(1);

    $this->actingAs($deck->user)->delete(route('slides.destroy', $deck))->assertRedirect(route('slides.index'));

    expect(SlideDeck::count())->toBe(0)->and(Slide::count())->toBe(0);
    Storage::disk('public')->assertMissing('slides/one.jpg');
});

it('lets only the maker edit a slideshow', function () {
    $deck = SlideDeck::factory()->for(User::factory()->member())->create();
    $other = User::factory()->member()->create();

    $this->actingAs($other)->get(route('slides.edit', $deck))->assertForbidden();
    $this->actingAs($other)->put(route('slides.update', $deck), ['title' => 'x'])->assertForbidden();

    $this->actingAs($deck->user)->get(route('slides.edit', $deck))->assertOk()->assertSee($deck->title);
});

it('renders the new slide template of the edit form without the id of a saved slide', function () {
    $deck = SlideDeck::factory()->for(User::factory()->member())->create();
    $slide = Slide::factory()->for($deck, 'deck')->create(['position' => 1]);

    $this->actingAs($deck->user)->get(route('slides.edit', $deck))
        ->assertOk()
        ->assertSee('name="slides[0][id]" value="'.$slide->id.'"', false)
        ->assertDontSee('name="slides[__INDEX__][id]"', false);
});

it('updates the title, text and order, replaces an image, removes a slide and adds a new one', function () {
    $deck = SlideDeck::factory()->for(User::factory()->member())->create(['code' => 'keep123']);
    $first = Slide::factory()->for($deck, 'deck')->create(['position' => 1, 'image' => 'slides/first.jpg', 'text' => 'old']);
    $second = Slide::factory()->for($deck, 'deck')->create(['position' => 2, 'image' => 'slides/second.jpg']);
    $third = Slide::factory()->for($deck, 'deck')->create(['position' => 3, 'image' => 'slides/third.jpg']);
    foreach (['first', 'second', 'third'] as $name) {
        Storage::disk('public')->put("slides/{$name}.jpg", 'x');
    }

    $this->actingAs($deck->user)->put(route('slides.update', $deck), [
        'title' => 'Tiêu đề mới',
        'slides' => [
            ['id' => $third->id, 'text' => 'moved first'],
            ['id' => $first->id, 'text' => '', 'image' => UploadedFile::fake()->image('new.png')],
            ['text' => 'brand new', 'image' => UploadedFile::fake()->image('add.jpg')],
        ],
    ])->assertRedirect(route('slides.index'))->assertSessionHasNoErrors();

    $deck->refresh();
    expect($deck->title)->toBe('Tiêu đề mới')->and($deck->code)->toBe('keep123');

    $slides = $deck->slides()->get();
    expect($slides)->toHaveCount(3)
        ->and($slides[0]->id)->toBe($third->id)->and($slides[0]->text)->toBe('moved first')->and($slides[0]->image)->toBe('slides/third.jpg')
        ->and($slides[1]->id)->toBe($first->id)->and($slides[1]->text)->toBeNull()->and($slides[1]->image)->not->toBe('slides/first.jpg')
        ->and($slides[2]->text)->toBe('brand new');

    expect(Slide::find($second->id))->toBeNull();
    Storage::disk('public')->assertMissing('slides/second.jpg');
    Storage::disk('public')->assertMissing('slides/first.jpg');
    Storage::disk('public')->assertExists('slides/third.jpg');
    Storage::disk('public')->assertExists($slides[1]->image);
});

it('rejects an edit with a new slide without image or a slide of another deck', function () {
    $deck = SlideDeck::factory()->for(User::factory()->member())->create();
    $foreign = Slide::factory()->create();

    $this->actingAs($deck->user)->put(route('slides.update', $deck), [
        'title' => 'x',
        'slides' => [['text' => 'no image']],
    ])->assertSessionHasErrors('slides.0.image');

    $this->actingAs($deck->user)->put(route('slides.update', $deck), [
        'title' => 'x',
        'slides' => [['id' => $foreign->id, 'text' => 'steal']],
    ])->assertSessionHasErrors('slides.0.id');
});
