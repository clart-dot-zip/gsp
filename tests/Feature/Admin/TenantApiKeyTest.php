<?php

use App\Models\Group;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\TenantApiKey;
use App\Models\TenantContact;
use App\Models\TenantActivityLog;
use App\Models\User;
use Illuminate\Support\Str;

function createUserWithPermission(string $permissionSlug): User
{
    $user = User::factory()->create();

    $permission = Permission::firstOrCreate(
        ['slug' => $permissionSlug],
        ['name' => Str::title(str_replace('_', ' ', $permissionSlug))]
    );

    $group = Group::create([
        'name' => 'test-'.$permissionSlug,
        'slug' => 'test-'.$permissionSlug.Str::random(6),
    ]);

    $group->permissions()->attach($permission);
    $user->groups()->attach($group);

    return $user->fresh();
}

test('admin can create data collector api key for a tenant', function () {
    $tenant = Tenant::create([
        'name' => 'Test Tenant',
        'slug' => Tenant::generateUniqueSlug('Test Tenant'),
    ]);

    $admin = createUserWithPermission('manage_api_keys');

    $response = $this->actingAs($admin)
        ->post(route('admin.tenants.api-keys.store', $tenant));

    $response->assertRedirect(route('admin.tenants.api-keys.index'));
    $response->assertSessionHas('new_key');

    $sessionKey = $response->getSession()->get('new_key');
    $plainKey = $sessionKey['value'];

    $apiKey = TenantApiKey::where('tenant_id', $tenant->id)->first();

    expect($apiKey)->not->toBeNull();
    expect($apiKey->key_type)->toBe(TenantApiKey::TYPE_DATA_COLLECTOR);
    expect($apiKey->last_four)->toBe(substr($plainKey, -4));
    expect($apiKey->key_hash)->toBe(hash('sha256', $plainKey));
});

test('users without permission cannot access tenant api key pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.tenants.api-keys.index'))
        ->assertForbidden();
});

test('data collector api rejects invalid keys', function () {
    $tenant = Tenant::create([
        'name' => 'API Tenant',
        'slug' => Tenant::generateUniqueSlug('API Tenant'),
    ]);

    $plainKey = 'collector-'.Str::random(16);

    TenantApiKey::create([
        'tenant_id' => $tenant->id,
        'name' => 'Data Collector',
        'key_type' => TenantApiKey::TYPE_DATA_COLLECTOR,
        'key_hash' => hash('sha256', $plainKey),
        'last_four' => substr($plainKey, -4),
    ]);

    $this->getJson('/api/v1/tenant')
        ->assertStatus(401);

    $this->withHeaders(['X-Api-Key' => 'invalid'])
        ->getJson('/api/v1/tenant')
        ->assertStatus(401);
});

test('data collector api returns tenant data with valid key', function () {
    $tenant = Tenant::create([
        'name' => 'Live Tenant',
        'slug' => Tenant::generateUniqueSlug('Live Tenant'),
        'contact_email' => 'tenant@example.com',
    ]);

    TenantContact::create([
        'tenant_id' => $tenant->id,
        'name' => 'Player One',
        'email' => 'player@example.com',
    ]);

    $log = TenantActivityLog::create([
        'tenant_id' => $tenant->id,
        'event' => 'seeded',
        'method' => 'API',
        'route_name' => 'seed',
        'path' => '/seed',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'tests',
    ]);

    $plainKey = 'collector-'.Str::random(16);

    TenantApiKey::create([
        'tenant_id' => $tenant->id,
        'name' => 'Data Collector',
        'key_type' => TenantApiKey::TYPE_DATA_COLLECTOR,
        'key_hash' => hash('sha256', $plainKey),
        'last_four' => substr($plainKey, -4),
    ]);

    $response = $this->withHeaders(['X-Api-Key' => $plainKey])
        ->getJson('/api/v1/tenant');

    $response->assertOk();
    $response->assertJsonPath('data.slug', $tenant->slug);

    $this->withHeaders(['X-Api-Key' => $plainKey])
        ->getJson('/api/v1/tenant/logs')
        ->assertOk()
        ->assertJsonFragment(['event' => $log->event]);
});
