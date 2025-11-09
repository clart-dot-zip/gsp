<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\TenantGroup;
use App\Models\TenantPermission;
use App\Models\TenantPlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create([
            'name' => 'Manage Tenant Permissions',
            'slug' => 'manage_tenant_permissions',
        ]);
    }

    public function test_admin_can_create_tenant_group(): void
    {
        $tenant = Tenant::create([
            'name' => 'Example Tenant',
            'slug' => Tenant::generateUniqueSlug('Example Tenant'),
        ]);

        $user = $this->createUserWithPermission('manage_tenant_permissions');

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post(route('tenants.permissions.groups.store', ['tenant' => $tenant]), [
                'name' => 'Moderators',
                'description' => 'People who keep the peace',
            ]);

        $response->assertRedirect(route('tenants.pages.show', ['page' => 'permissions_groups']));

        $this->assertDatabaseHas('tenant_groups', [
            'tenant_id' => $tenant->id,
            'name' => 'Moderators',
        ]);
    }

    public function test_admin_can_sync_group_permissions(): void
    {
        $tenant = Tenant::create([
            'name' => 'Sync Tenant',
            'slug' => Tenant::generateUniqueSlug('Sync Tenant'),
        ]);

        $user = $this->createUserWithPermission('manage_tenant_permissions');

        $group = TenantGroup::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admins',
            'slug' => TenantGroup::generateUniqueSlug('Admins', $tenant->id),
        ]);

        $permission = TenantPermission::create([
            'tenant_id' => $tenant->id,
            'name' => 'ulx noclip',
            'slug' => TenantPermission::generateUniqueSlug('ulx noclip', $tenant->id),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post(route('tenants.permissions.groups.permissions.sync', ['tenant' => $tenant, 'group' => $group]), [
                'permissions' => [$permission->id],
            ]);

        $response->assertRedirect(route('tenants.pages.show', ['page' => 'permissions_group_permissions']));

        $this->assertDatabaseHas('tenant_group_permission', [
            'tenant_group_id' => $group->id,
            'tenant_permission_id' => $permission->id,
        ]);
    }

    public function test_admin_can_assign_group_to_player(): void
    {
        $tenant = Tenant::create([
            'name' => 'Player Tenant',
            'slug' => Tenant::generateUniqueSlug('Player Tenant'),
        ]);

        $user = $this->createUserWithPermission('manage_tenant_permissions');

        $group = TenantGroup::create([
            'tenant_id' => $tenant->id,
            'name' => 'Trusted',
            'slug' => TenantGroup::generateUniqueSlug('Trusted', $tenant->id),
        ]);

        $player = TenantPlayer::create([
            'tenant_id' => $tenant->id,
            'display_name' => 'Test Player',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post(route('tenants.permissions.players.groups.attach', ['tenant' => $tenant, 'player' => $player]), [
                'group_id' => $group->id,
            ]);

        $response->assertRedirect(route('tenants.pages.show', ['page' => 'permissions_users']));

        $this->assertDatabaseHas('tenant_player_group', [
            'tenant_group_id' => $group->id,
            'tenant_player_id' => $player->id,
        ]);
    }

    protected function createUserWithPermission(string $permissionSlug): User
    {
        $user = User::factory()->create();

        $group = Group::create([
            'name' => 'Test Admins',
            'slug' => 'test-admins',
        ]);

        $permission = Permission::where('slug', $permissionSlug)->firstOrFail();
        $group->permissions()->sync([$permission->id]);

        $user->groups()->sync([$group->id]);

        return $user;
    }
}
