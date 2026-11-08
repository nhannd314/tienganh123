<?php

use App\Enums\Membership;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('lists the learners and leaves the admins out', function () {
    $learner = User::factory()->create();
    $otherAdmin = User::factory()->admin()->create();

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$learner])
        ->assertCanNotSeeTableRecords([$this->admin, $otherAdmin]);
});

it('does not open the edit page of an admin', function () {
    $this->get(route('filament.admin.resources.users.edit', ['record' => $this->admin]))->assertNotFound();
});

it('creates a learner with a hashed password and the user role', function () {
    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'Học Sinh', 'email' => 'hs@example.com', 'password' => 'secret-pass'])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::firstWhere('email', 'hs@example.com');

    expect($user->isAdmin())->toBeFalse()
        ->and(Hash::check('secret-pass', $user->password))->toBeTrue();
});

it('keeps the password when it is left empty on edit', function () {
    $learner = User::factory()->create();
    $password = $learner->password;

    Livewire::test(EditUser::class, ['record' => $learner->getRouteKey()])
        ->fillForm(['name' => 'Tên mới', 'password' => ''])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($learner->fresh())->name->toBe('Tên mới')->password->toBe($password);
});

it('makes a learner a member until a given day', function () {
    $learner = User::factory()->create();

    Livewire::test(EditUser::class, ['record' => $learner->getRouteKey()])
        ->fillForm(['membership' => Membership::Member->value, 'membership_expires_on' => '2027-12-31'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($learner->fresh())
        ->membership->toBe(Membership::Member)
        ->membership_expires_on->toDateString()->toBe('2027-12-31');
});

it('makes the selected learners members at once and leaves the others alone', function () {
    [$first, $second, $other] = User::factory()->count(3)->create();

    Livewire::test(ListUsers::class)
        ->selectTableRecords([$first->getKey(), $second->getKey()])
        ->callAction(TestAction::make('grantMembership')->table()->bulk(), ['membership' => Membership::Member->value, 'membership_expires_on' => '2027-12-31'])
        ->assertHasNoFormErrors()
        ->assertNotified('Đã thêm gói thành viên');

    expect($first->fresh())->membership->toBe(Membership::Member)->membership_expires_on->toDateString()->toBe('2027-12-31')
        ->and($second->fresh())->membership->toBe(Membership::Member)
        ->and($other->fresh()->membership)->toBeNull();
});

it('turns off autofill for the phone and password of a learner', function () {
    Livewire::test(CreateUser::class)
        ->assertSeeHtml('autocomplete="new-password"')
        ->assertSeeHtml('autocomplete="off"');
});

it('does not let the email of a learner be changed', function () {
    $learner = User::factory()->create(['email' => 'old@example.com']);

    Livewire::test(EditUser::class, ['record' => $learner->getRouteKey()])
        ->assertFormFieldIsDisabled('email')
        ->fillForm(['email' => 'new@example.com', 'name' => 'Tên mới'])
        ->call('save');

    expect($learner->fresh())->email->toBe('old@example.com')->name->toBe('Tên mới');
});

it('imports a learner for each line with the default password, skipping invalid and already used ones', function () {
    User::factory()->create(['email' => 'old@example.com']);

    Livewire::test(ListUsers::class)
        ->callAction('importUsers', [
            'user_lines' => "Nguyễn Văn An | AN@example.com | +84 912 345 678\n\nTrần Bình | binh@example.com\nCũ | old@example.com | 0900000000\n | none@example.com\nSai | not-an-email",
        ])
        ->assertHasNoFormErrors()
        ->assertNotified('Imported 2 users');

    $an = User::firstWhere('email', 'an@example.com');

    expect($an)
        ->name->toBe('Nguyễn Văn An')
        ->phone->toBe('0912345678')
        ->membership->toBeNull()
        ->and(Hash::check('12345678', $an->password))->toBeTrue()
        ->and($an->isAdmin())->toBeFalse()
        ->and(User::firstWhere('email', 'binh@example.com')->phone)->toBeNull()
        ->and(User::count())->toBe(4);
});

it('gives the imported learners a membership that never expires when the box is ticked', function () {
    Livewire::test(ListUsers::class)
        ->callAction('importUsers', ['user_lines' => 'Lê Chi | chi@example.com | 0987654321', 'is_member' => true])
        ->assertHasNoFormErrors();

    expect(User::firstWhere('email', 'chi@example.com'))
        ->membership->toBe(Membership::Member)
        ->membership_expires_on->toBeNull();
});
