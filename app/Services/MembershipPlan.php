<?php

namespace App\Services;

/**
 * The price list of the membership, read from config payment.membership: one monthly price, a crossed-out regular
 * price, and the cycles (months) it can be bought for with the percentage taken off each.
 */
class MembershipPlan
{
    /**
     * The months the membership can be bought for, from the shortest, with the percentage off each.
     *
     * @return array<int, int>
     */
    public static function cycles(): array
    {
        return config('payment.membership.cycles');
    }

    /**
     * Whether the number of months is one of the cycles.
     */
    public static function hasCycle(int $months): bool
    {
        return array_key_exists($months, self::cycles());
    }

    /**
     * The cycle chosen when none is asked for: the shortest one.
     */
    public static function defaultMonths(): int
    {
        return array_key_first(self::cycles());
    }

    /**
     * The price of one month, in VND.
     */
    public static function monthlyPrice(): int
    {
        return (int) config('payment.membership.monthly_price');
    }

    /**
     * The crossed-out price of one month before the sale, in VND.
     */
    public static function regularPrice(): int
    {
        return max((int) config('payment.membership.regular_price'), self::monthlyPrice());
    }

    /**
     * The price of the given number of months, with the discount of that cycle taken off, in VND.
     */
    public static function priceForMonths(int $months): int
    {
        $discount = self::cycles()[$months] ?? 0;

        return (int) round(self::monthlyPrice() * $months * (100 - $discount) / 100);
    }

    /**
     * The given number of months at the regular price, in VND.
     */
    public static function regularPriceForMonths(int $months): int
    {
        return self::regularPrice() * $months;
    }

    /**
     * The percentage the price of the given number of months saves off the same months at the regular price, rounded.
     */
    public static function discountPercentForMonths(int $months): int
    {
        $regularPrice = self::regularPriceForMonths($months);

        if ($regularPrice <= 0) {
            return 0;
        }

        return max(0, (int) round(($regularPrice - self::priceForMonths($months)) * 100 / $regularPrice));
    }

    /**
     * An amount of VND as shown to the learner, e.g. "99.000₫".
     */
    public static function format(int $amount): string
    {
        return number_format($amount, 0, ',', '.').'₫';
    }
}
