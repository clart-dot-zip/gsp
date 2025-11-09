<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use App\Support\TenantPageAuthorization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

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
            ['slug' => 'manage_tenant_permissions', 'name' => 'Manage Tenant Permissions'],
            ['slug' => 'manage_access', 'name' => 'Manage Access Control'],
            ['slug' => 'manage_api_keys', 'name' => 'Manage Tenant API Keys'],
            ['slug' => 'manage_support_tickets', 'name' => 'Manage Support Tickets'],
        ];

        $tenantCategories = Config::get('tenant.categories', []);

        foreach ($tenantCategories as $category) {
            foreach (($category['pages'] ?? []) as $pageKey => $pageTitle) {
                $permissions[] = [
                    'slug' => TenantPageAuthorization::permissionForPage((string) $pageKey),
                    'name' => 'View Tenant Page: '.$pageTitle,
                ];
            }
        }

        $permissions = array_merge($permissions, [
            ['slug' => 'support_tickets_create', 'name' => 'Create Support Tickets'],
            ['slug' => 'support_tickets_comment', 'name' => 'Comment on Support Tickets'],
            ['slug' => 'support_tickets_attach', 'name' => 'Upload Support Ticket Attachments'],
            ['slug' => 'support_tickets_collaborate', 'name' => 'Collaborate on Support Tickets'],
        ]);

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

        $legacyPlayerGroup = Group::firstWhere('slug', 'tenant-player');
        if ($legacyPlayerGroup) {
            $legacyPlayerGroup->slug = 'player';
            $legacyPlayerGroup->name = 'Players';
            $legacyPlayerGroup->save();
        }

        $tenantPlayerGroup = Group::firstOrCreate(
            ['slug' => 'player'],
            ['name' => 'Players']
        );

        $allPermissionIds = Permission::pluck('id', 'slug');

        $adminGroup->permissions()->sync($allPermissionIds->values()->all());

        $tenantContactPermissions = $allPermissionIds
            ->only([
                'view_dashboard',
                'view_tenant_pages',
                'support_tickets_collaborate',
                'support_tickets_comment',
                'support_tickets_attach',
                'support_tickets_create',
                TenantPageAuthorization::permissionForPage('support_tickets'),
            ])
            ->filter()
            ->values()
            ->all();

        $tenantContactGroup->permissions()->sync($tenantContactPermissions);

        $tenantPlayerPermissions = $allPermissionIds
            ->only([
                TenantPageAuthorization::permissionForPage('overview'),
                TenantPageAuthorization::permissionForPage('support_tickets'),
                'support_tickets_create',
                'support_tickets_comment',
                'support_tickets_attach',
            ])
            ->filter()
            ->values()
            ->all();

        $tenantPlayerGroup->permissions()->sync($tenantPlayerPermissions);

        User::whereDoesntHave('groups')->each(function (User $user) use ($adminGroup) {
            $user->groups()->syncWithoutDetaching([$adminGroup->id]);
        });
    }
}
