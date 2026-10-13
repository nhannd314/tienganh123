<?php

namespace Database\Seeders;

use App\Models\AiService;
use App\Models\MembershipPlan;
use App\Models\TokenPackage;
use Illuminate\Database\Seeder;

class PricingSeeder extends Seeder
{
    /**
     * Seed the sample prices. They are starting points: every one is edited in the admin.
     */
    public function run(): void
    {
        MembershipPlan::updateOrCreate(
            ['slug' => 'thanh-vien'],
            ['name' => 'Thành viên', 'description' => 'Học tất cả các khóa học.', 'monthly_price' => 100000, 'is_active' => true],
        );

        foreach ([
            ['name' => 'Gói 50 token', 'tokens' => 50, 'price' => 25000],
            ['name' => 'Gói 120 token', 'tokens' => 120, 'price' => 50000],
            ['name' => 'Gói 300 token', 'tokens' => 300, 'price' => 100000],
        ] as $index => $package) {
            TokenPackage::updateOrCreate(['name' => $package['name']], [...$package, 'sort_order' => $index + 1, 'is_active' => true]);
        }

        AiService::updateOrCreate(
            ['key' => 'pronunciation_scoring'],
            ['name' => 'Chấm điểm phát âm', 'tokens_per_use' => 1, 'is_active' => true],
        );
    }
}
