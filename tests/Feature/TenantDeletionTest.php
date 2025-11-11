<?php

use App\Models\Group;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function createTenantManagerUser(): User
{
    $user = User::factory()->create();

    $group = Group::firstOrCreate(
        ['slug' => 'tenant-managers'],
        ['name' => 'Tenant Managers']
    );

    $permission = Permission::firstOrCreate(
        ['slug' => 'manage_tenants'],
        ['name' => 'Manage Tenants']
    );

    $group->permissions()->syncWithoutDetaching([$permission->id]);
    $user->groups()->syncWithoutDetaching([$group->id]);

    return $user;
}

function createTenant(string $name = 'Test Tenant'): Tenant
{
    return Tenant::create([
        'name' => $name,
        'slug' => Tenant::generateUniqueSlug($name),
    ]);
}

it('requires typing the tenant name exactly before deletion', function () {
    $tenant = createTenant('Example Tenant');
    $user = createTenantManagerUser();

    $response = actingAs($user)
        ->from(route('tenants.manage'))
        ->delete(route('tenants.destroy', $tenant), [
            'delete_confirmation_name' => 'ExampleTenant',
            'delete_confirmation_tenant_id' => $tenant->id,
        ]);

    $response->assertRedirect(route('tenants.manage'));
    $response->assertSessionHasErrorsIn('tenantDeletion', ['delete_confirmation_name']);
    $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
});

it('deletes the tenant when confirmation matches', function () {
    $tenant = createTenant('Delete Me');
    $user = createTenantManagerUser();

    $response = actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->from(route('tenants.manage'))
        ->delete(route('tenants.destroy', $tenant), [
            'delete_confirmation_name' => 'Delete Me',
            'delete_confirmation_tenant_id' => $tenant->id,
        ]);

    $response->assertRedirect(route('tenants.manage'));
    $response->assertSessionHas('status', 'Tenant deleted successfully.');
    $response->assertSessionMissing('tenant_id');
    $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);
});
