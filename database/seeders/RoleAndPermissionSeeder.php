<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view',
            'sync',
            'manage-visibility',
            'sap.post',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $accountantRole = Role::firstOrCreate(['name' => 'Accountant', 'guard_name' => 'web']);
        $managerRole = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $legacyAdminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $legacyManagerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $legacyUserRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $adminRole->syncPermissions($permissions);
        $accountantRole->syncPermissions(['view', 'sync', 'sap.post']);
        $managerRole->syncPermissions(['view']);
        $legacyAdminRole->syncPermissions($permissions);
        $legacyManagerRole->syncPermissions(['view']);
        $legacyUserRole->syncPermissions(['view']);

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@arkfleet.local'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'password' => 'password',
            ],
        );

        $accountant = User::query()->updateOrCreate(
            ['email' => 'accountant@arkfleet.local'],
            [
                'name' => 'Accountant',
                'username' => 'accountant',
                'password' => 'password',
            ],
        );

        $admin->syncRoles([$adminRole->name, $legacyAdminRole->name]);
        $accountant->syncRoles([$accountantRole->name]);
    }
}
