<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;

class AccessControlSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['slug' => 'root', 'name' => 'Root Access'],
            ['slug' => 'view_dashboard', 'name' => 'View Dashboard'],
            ['slug' => 'view_tenant_pages', 'name' => 'View Tenant Pages'],
            ['slug' => 'manage_tenants', 'name' => 'Manage Tenants'],
            ['slug' => 'manage_contacts', 'name' => 'Manage Contacts'],
            ['slug' => 'manage_access', 'name' => 'Manage Access Control'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::updateOrCreate(
                ['slug' => $permissionData['slug']],
                ['name' => $permissionData['name']]
            );
        }

        $adminGroup = Group::firstOrCreate(
            ['slug' => 'administrators'],
            ['name' => 'Administrators']
        );

        $tenantContactGroup = Group::firstOrCreate(
            ['slug' => 'tenant-contact'],
            ['name' => 'Tenant Contacts']
        );

        $allPermissionIds = Permission::pluck('id', 'slug');

        $adminGroup->permissions()->sync($allPermissionIds->values()->all());

        $tenantContactPermissions = $allPermissionIds
            ->only(['view_dashboard', 'view_tenant_pages'])
            ->filter()
            ->values()
            ->all();

        $tenantContactGroup->permissions()->sync($tenantContactPermissions);

        User::whereDoesntHave('groups')->each(function (User $user) use ($adminGroup) {
            $user->groups()->syncWithoutDetaching([$adminGroup->id]);
        });
    }
}
