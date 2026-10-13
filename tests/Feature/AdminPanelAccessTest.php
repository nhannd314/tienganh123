<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets only admins into the admin panel', function () {
    $panel = Filament::getPanel('admin');

    expect(User::factory()->admin()->create()->canAccessPanel($panel))->toBeTrue()
        ->and(User::factory()->create()->canAccessPanel($panel))->toBeFalse();
});

it('shows the admin panel to an admin and forbids it to a regular user', function () {
    $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk();

    auth()->logout();

    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
});

it('sends a guest to the admin login', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});
