<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $this->createAdminUser();
        
        // Only create dummy users in non-production
        if (app()->environment(['local', 'development', 'testing'])) {
            $this->createDummyUsers();
        }
    }

    private function createAdminUser(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@sewarumah.com'],
            [
                'name' => 'Admin SewaRumah',
                'password' => Hash::make('password'),
                'phone' => '081234567890',
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );

        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        $this->command->info("Admin user created: {$admin->email}");
    }

    private function createDummyUsers(): void
    {
        // Owner 1 - Verified
        $owner1 = User::firstOrCreate(
            ['email' => 'owner1@sewarumah.com'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('password'),
                'phone' => '081234567891',
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );
        if (!$owner1->hasRole('owner')) {
            $owner1->assignRole('owner');
        }
        $this->command->info("Owner (verified) created: {$owner1->email}");

        // Owner 2 - Unverified
        $owner2 = User::firstOrCreate(
            ['email' => 'owner2@sewarumah.com'],
            [
                'name' => 'Dewi Lestari',
                'password' => Hash::make('password'),
                'phone' => '081234567892',
                'is_verified' => false,
                'email_verified_at' => now(),
            ]
        );
        if (!$owner2->hasRole('owner')) {
            $owner2->assignRole('owner');
        }
        $this->command->info("Owner (unverified) created: {$owner2->email}");

        // Tenant 1
        $tenant1 = User::firstOrCreate(
            ['email' => 'tenant1@sewarumah.com'],
            [
                'name' => 'Andi Wijaya',
                'password' => Hash::make('password'),
                'phone' => '081234567893',
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );
        if (!$tenant1->hasRole('tenant')) {
            $tenant1->assignRole('tenant');
        }
        $this->command->info("Tenant created: {$tenant1->email}");

        // Tenant 2
        $tenant2 = User::firstOrCreate(
            ['email' => 'tenant2@sewarumah.com'],
            [
                'name' => 'Siti Rahayu',
                'password' => Hash::make('password'),
                'phone' => '081234567894',
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );
        if (!$tenant2->hasRole('tenant')) {
            $tenant2->assignRole('tenant');
        }
        $this->command->info("Tenant created: {$tenant2->email}");
    }
}