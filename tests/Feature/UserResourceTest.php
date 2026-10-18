<?php

use App\Enums\Membership;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
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

it('does not let the email of a learner be changed', function () {
    $learner = User::factory()->create(['email' => 'old@example.com']);

    Livewire::test(EditUser::class, ['record' => $learner->getRouteKey()])
        ->assertFormFieldIsDisabled('email')
        ->fillForm(['email' => 'new@example.com', 'name' => 'Tên mới'])
        ->call('save');

    expect($learner->fresh())->email->toBe('old@example.com')->name->toBe('Tên mới');
});
