<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $adminRole = Role::firstOrCreate([
            'name' => 'Admin',
            'guard_name' => 'api',
        ]);

        $userRole = Role::firstOrCreate([
            'name' => 'User',
            'guard_name' => 'api',
        ]);

        $permissions = [
            'view_users',
            'edit_users',
            'delete_users'
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'api'
            ]);
        }

        $adminRole->syncPermissions($permissions);

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('123456789')
            ]
        );

        $admin->assignRole($adminRole);

        $this->command->info('Roles and users have been seeded successfully.');
    }
}
