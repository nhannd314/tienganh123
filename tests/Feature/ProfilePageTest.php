<?php

use App\Models\Course;
use App\Models\LessonReview;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('sends a guest to sign in', function () {
    $this->get(route('profile.show'))->assertRedirect(route('login'));
    $this->put(route('profile.update'), ['name' => 'An'])->assertRedirect(route('login'));
});

it('shows the avatar linking to the profile in the header of a signed-in visitor', function () {
    $user = User::factory()->create(['name' => 'Nguyễn Minh An']);

    $this->actingAs($user)->get(route('courses.index'))
        ->assertSee('href="'.route('profile.show').'"', false)
        ->assertSee('Hồ sơ của Nguyễn Minh An');
});

it('shows the achievements by default', function () {
    $user = User::factory()->create();
    $course = Course::factory()->published()->create(['title' => 'Hello English']);
    Subscription::factory()->create(['user_id' => $user->id, 'subscribable_type' => $course->getMorphClass(), 'subscribable_id' => $course->id]);
    LessonReview::factory()->create(['user_id' => $user->id, 'correct' => 9, 'total' => 10]);

    $this->actingAs($user)->get(route('profile.show'))
        ->assertOk()
        ->assertSee('Thành tích học tập')
        ->assertSee('Hello English')
        ->assertSee('9/10');
});

it('shows the personal information and lets the learner edit it', function () {
    $user = User::factory()->create(['name' => 'Nguyễn Minh An']);

    $this->actingAs($user)->get(route('profile.show', ['tab' => 'info']))
        ->assertOk()
        ->assertSee('Nguyễn Minh An')
        ->assertSee('Chỉnh sửa')
        ->assertSee($user->email);

    $this->actingAs($user)->get(route('profile.show', ['tab' => 'info', 'edit' => 1]))
        ->assertSee('Lưu thay đổi');
});

it('saves the personal information', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('profile.update'), [
        'name' => 'Trần Bảo Ngọc',
        'birthday' => '05/03/2016',
        'grade' => 'Lớp 3',
        'school' => 'Tiểu học Kim Đồng',
        'parent_email' => 'ba@example.com',
        'parent_phone' => '0901234567',
    ])->assertRedirect(route('profile.show', ['tab' => 'info']));

    $user->refresh();
    expect($user->name)->toBe('Trần Bảo Ngọc')
        ->and($user->birthday->toDateString())->toBe('2016-03-05')
        ->and($user->grade)->toBe('Lớp 3')
        ->and($user->parent_email)->toBe('ba@example.com');
});

it('rejects an invalid profile', function () {
    $user = User::factory()->create(['name' => 'Giữ Nguyên']);

    $this->actingAs($user)->from(route('profile.show', ['tab' => 'info']))->put(route('profile.update'), [
        'name' => '',
        'birthday' => '2016-03-05',
        'parent_email' => 'not-an-email',
        'parent_phone' => 'abc',
    ])->assertSessionHasErrors(['name', 'birthday', 'parent_email', 'parent_phone']);

    expect($user->refresh()->name)->toBe('Giữ Nguyên');
});

it('lists the courses the learner subscribed to', function () {
    $user = User::factory()->create();
    $course = Course::factory()->published()->create(['title' => 'Tiếng Anh lớp 3']);
    Subscription::factory()->create(['user_id' => $user->id, 'subscribable_type' => $course->getMorphClass(), 'subscribable_id' => $course->id, 'price_paid' => 60000]);

    $this->actingAs($user)->get(route('profile.show', ['tab' => 'courses']))
        ->assertOk()
        ->assertSee('Tiếng Anh lớp 3')
        ->assertSee('60.000₫')
        ->assertSee(route('courses.show', $course), false);
});

it('falls back to the achievements for an unknown tab', function () {
    $this->actingAs(User::factory()->create())->get(route('profile.show', ['tab' => 'nope']))
        ->assertOk()
        ->assertSee('Tiến độ khóa học');
});

it('saves the phone number and an uploaded avatar, replacing the old one', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('profile.update'), [
        'name' => $user->name,
        'phone' => '0912345678',
        'avatar' => UploadedFile::fake()->image('first.png', 1600, 1000),
    ])->assertSessionHasNoErrors();

    $firstAvatar = $user->refresh()->avatar;
    expect($user->phone)->toBe('0912345678');
    Storage::disk('public')->assertExists($firstAvatar);
    [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($firstAvatar));
    expect([$width, $height])->toBe([512, 512]);

    $this->actingAs($user)->put(route('profile.update'), [
        'name' => $user->name,
        'avatar' => UploadedFile::fake()->image('second.png'),
    ]);

    Storage::disk('public')->assertMissing($firstAvatar);
    Storage::disk('public')->assertExists($user->refresh()->avatar);
});

it('rejects an avatar that is not an image', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('profile.update'), [
        'name' => $user->name,
        'avatar' => UploadedFile::fake()->create('file.pdf', 10, 'application/pdf'),
    ])->assertSessionHasErrors('avatar');
});

it('changes the password when the current one is right', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('profile.password.update'), [
        'current_password' => 'password',
        'password' => 'newsecret',
        'password_confirmation' => 'newsecret',
    ])->assertSessionHasNoErrors()->assertRedirect(route('profile.show', ['tab' => 'info']));

    expect(Hash::check('newsecret', $user->refresh()->password))->toBeTrue();
});

it('keeps the password when the current one is wrong or the confirmation differs', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('profile.password.update'), [
        'current_password' => 'wrong',
        'password' => 'newsecret',
        'password_confirmation' => 'other',
    ])->assertSessionHasErrors(['current_password', 'password']);

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

it('lets a Google account set a password without the current one', function () {
    $user = User::factory()->create(['google_id' => 'g-123']);

    $this->actingAs($user)->put(route('profile.password.update'), [
        'password' => 'newsecret',
        'password_confirmation' => 'newsecret',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('newsecret', $user->refresh()->password))->toBeTrue();
});

it('keeps the password form closed until it has errors', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('profile.show', ['tab' => 'info']))
        ->assertSee('Đổi mật khẩu')
        ->assertDontSee('<details class="group" open>', false);

    $this->actingAs($user)->from(route('profile.show', ['tab' => 'info']))
        ->put(route('profile.password.update'), ['current_password' => 'wrong', 'password' => 'newsecret', 'password_confirmation' => 'newsecret'])
        ->assertRedirect(route('profile.show', ['tab' => 'info']));

    $this->get(route('profile.show', ['tab' => 'info']))
        ->assertSee('<details class="group" open>', false);
});
