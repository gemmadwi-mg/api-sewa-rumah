<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions
        $permissions = [
            // Profile
            'view-profile',
            'update-profile',

            // Property Management (Owner)
            'view-own-properties',
            'create-property',
            'update-own-property',
            'delete-own-property',
            'upload-property-images',

            // Property Listing (Public/Tenant)
            'view-all-properties',

            // Booking (Tenant)
            'create-booking',
            'view-own-bookings',
            'cancel-own-booking',

            // Booking (Owner)
            'view-property-bookings',
            'approve-booking',
            'reject-booking',

            // Review
            'create-review',

            // Admin
            'manage-users',
            'manage-properties',
            'verify-owner',
            'verify-property',
            'view-reports',
            'view-all-bookings',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api',
            ]);
        }

        // Create roles and assign permissions
        $this->createOwnerRole();
        $this->createTenantRole();
        $this->createAdminRole();

        $this->command->info('Roles and permissions seeded successfully!');
    }

    private function createOwnerRole(): void
    {
        $role = Role::firstOrCreate([
            'name' => 'owner',
            'guard_name' => 'api',
        ]);

        $permissions = [
            'view-profile',
            'update-profile',
            'view-own-properties',
            'create-property',
            'update-own-property',
            'delete-own-property',
            'upload-property-images',
            'view-property-bookings',
            'approve-booking',
            'reject-booking',
        ];

        $role->syncPermissions($permissions);
    }

    private function createTenantRole(): void
    {
        $role = Role::firstOrCreate([
            'name' => 'tenant',
            'guard_name' => 'api',
        ]);

        $permissions = [
            'view-profile',
            'update-profile',
            'view-all-properties',
            'create-booking',
            'view-own-bookings',
            'cancel-own-booking',
            'create-review',
        ];

        $role->syncPermissions($permissions);
    }

    private function createAdminRole(): void
    {
        $role = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'api',
        ]);

        // Admin gets all permissions
        $allPermissions = Permission::where('guard_name', 'api')->get();
        $role->syncPermissions($allPermissions);
    }
}