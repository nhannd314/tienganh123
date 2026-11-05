<?php

use App\Enums\SubscriptionStatus;
use App\Filament\Resources\Subscriptions\Pages\ListSubscriptions;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Models\Course;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
    $this->freezeTime();
});

/**
 * A subscription that runs between the given days from today, with null for no end.
 *
 * @param  array<string, mixed>  $attributes
 */
function subscriptionBetween(int $startsInDays, ?int $endsInDays, array $attributes = []): Subscription
{
    return Subscription::factory()->create([
        'starts_at' => now()->addDays($startsInDays),
        'ends_at' => $endsInDays === null ? null : now()->addDays($endsInDays),
        ...$attributes,
    ]);
}

it('shows who bought which course, for how long, from when, until when and how many days are left', function () {
    $this->travelTo('2026-11-06 10:00:00');
    $user = User::factory()->create(['name' => 'Nguyễn Văn A', 'email' => 'a@example.com']);
    $course = Course::factory()->create(['title' => 'Tiếng Anh lớp 3']);

    $subscription = Subscription::factory()->for($user)->create([
        'subscribable_id' => $course->id,
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonths(2),
        'price_paid' => 150000,
    ]);

    Livewire::test(ListSubscriptions::class)
        ->assertCanSeeTableRecords([$subscription])
        ->assertSeeInOrder(['Nguyễn Văn A', 'a@example.com', 'Tiếng Anh lớp 3', '3 tháng', '06/10/2026', '06/01/2027', 'Còn 61 ngày', 'Đang học', '150.000 ₫']);
});

it('shows a subscription without an end as never expiring', function () {
    $subscription = Subscription::factory()->free()->create();

    expect($subscription->currentStatus())->toBe(SubscriptionStatus::Active)
        ->and($subscription->daysRemaining())->toBeNull()
        ->and($subscription->durationInMonths())->toBeNull();

    Livewire::test(ListSubscriptions::class)->assertSee('Không giới hạn')->assertSee('Đang học');
});

it('counts a day that has started as a day left, and nothing once it is over', function () {
    expect(subscriptionBetween(-1, 7)->daysRemaining())->toBe(7)
        ->and(Subscription::factory()->create(['ends_at' => now()->addHour()])->daysRemaining())->toBe(1)
        ->and(subscriptionBetween(-40, -10)->daysRemaining())->toBe(0);
});

it('tells the status of a subscription and filters by it, at the edges of each', function () {
    $subscriptions = [
        'upcoming' => subscriptionBetween(1, 31),
        'active' => subscriptionBetween(-1, 8),
        'expiring_soon' => subscriptionBetween(-1, 7),
        'expired' => subscriptionBetween(-31, 0),
        'cancelled' => subscriptionBetween(-1, 30, ['cancelled_at' => now()]),
    ];

    foreach ($subscriptions as $status => $subscription) {
        expect($subscription->currentStatus())->toBe(SubscriptionStatus::from($status));

        Livewire::test(ListSubscriptions::class)
            ->filterTable('status', $status)
            ->assertCanSeeTableRecords([$subscription])
            ->assertCanNotSeeTableRecords(collect($subscriptions)->except($status)->values()->all());
    }
});

it('filters by course and searches by the name or email of the customer', function () {
    $course = Course::factory()->create();
    $wanted = Subscription::factory()->for(User::factory()->state(['name' => 'Trần Thị B', 'email' => 'b@example.com']))->create(['subscribable_id' => $course->id]);
    $other = Subscription::factory()->create();

    Livewire::test(ListSubscriptions::class)
        ->filterTable('course', $course->id)
        ->assertCanSeeTableRecords([$wanted])
        ->assertCanNotSeeTableRecords([$other]);

    foreach (['Trần Thị B', 'b@example.com'] as $search) {
        Livewire::test(ListSubscriptions::class)
            ->searchTable($search)
            ->assertCanSeeTableRecords([$wanted])
            ->assertCanNotSeeTableRecords([$other]);
    }
});

it('offers only the list: no page to create or edit a subscription', function () {
    expect(array_keys(SubscriptionResource::getPages()))->toBe(['index']);
});

it('keeps the page from anyone but an admin', function () {
    $this->actingAs(User::factory()->create())
        ->get(SubscriptionResource::getUrl('index'))
        ->assertForbidden();
});
