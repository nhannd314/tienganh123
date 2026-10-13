<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the admin user, or reset it when it already exists.
     */
    public function run(): void
    {
        User::firstOrNew(['email' => 'nhannd314@gmail.com'])
            ->forceFill([
                'name' => 'Nguyen Duy Nhan',
                'password' => '01051992',
                'role' => UserRole::Admin,
            ])
            ->save();
    }
}
