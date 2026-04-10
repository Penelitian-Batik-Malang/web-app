<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'Admin')->first();
        $userRole = Role::where('name', 'User')->first();

        User::firstOrCreate(
            ['email' => 'admin@email.com'],
            [
                'name' => 'Admin Testing',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id ?? null,
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'user@email.com'],
            [
                'name' => 'User Testing',
                'password' => Hash::make('password'),
                'role_id' => $userRole->id ?? null,
                'email_verified_at' => now(),
            ]
        );
    }
}
