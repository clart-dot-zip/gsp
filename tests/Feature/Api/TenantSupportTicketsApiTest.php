<?php

use App\Models\Tenant;
use App\Models\TenantApiKey;
use App\Models\TenantPlayer;
use App\Models\TenantSupportTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('creates a support ticket via the tenant api', function () {
    Carbon::setTestNow('2025-11-10 18:30:00');

    $tenant = Tenant::create([
        'name' => 'API Tenant',
        'slug' => Tenant::generateUniqueSlug('API Tenant'),
    ]);

    $reportingPlayer = TenantPlayer::create([
        'tenant_id' => $tenant->id,
        'display_name' => 'Reporter',
        'steam_id' => '76561198000000000',
    ]);

    $relatedPlayer = TenantPlayer::create([
        'tenant_id' => $tenant->id,
        'display_name' => 'Helper',
        'steam_id' => '76561198000000001',
    ]);

    $plainKey = 'collector-'.Str::random(20);

    TenantApiKey::create([
        'tenant_id' => $tenant->id,
        'name' => 'Game Server',
        'key_type' => TenantApiKey::TYPE_DATA_COLLECTOR,
        'key_hash' => hash('sha256', $plainKey),
        'last_four' => substr($plainKey, -4),
    ]);

    $payload = [
        'subject' => 'Crash during vote',
        'description' => 'Server crashed while changing map.',
        'priority' => 'HIGH',
        'external_reference' => 'ticket-123',
        'created_by_player_id' => $reportingPlayer->id,
        'player_ids' => [$relatedPlayer->id],
        'opened_at' => '2025-11-10T18:25:00+00:00',
    ];

    $response = $this->withHeaders(['X-Api-Key' => $plainKey])
        ->postJson('/api/v1/tenant/support-tickets', $payload);

    $response->assertCreated();
    $response->assertJsonPath('data.subject', 'Crash during vote');
    $response->assertJsonPath('data.priority', 'high');
    $response->assertJsonPath('data.created_by.player.id', $reportingPlayer->id);
    $response->assertJsonPath('data.players.0.id', $reportingPlayer->id);
    $response->assertJsonPath('data.players.1.id', $relatedPlayer->id);

    $ticket = TenantSupportTicket::first();
    expect($ticket)
        ->not()->toBeNull()
        ->and($ticket->tenant_id)->toBe($tenant->id)
        ->and($ticket->priority)->toBe(TenantSupportTicket::PRIORITY_HIGH)
        ->and($ticket->created_by_player_id)->toBe($reportingPlayer->id)
        ->and($ticket->external_reference)->toBe('ticket-123');

    expect($ticket->players()->pluck('tenant_player_id')->sort()->values()->all())
        ->toBe([$reportingPlayer->id, $relatedPlayer->id]);
});

it('returns existing ticket when external reference already exists', function () {
    $tenant = Tenant::create([
        'name' => 'Existing Tenant',
        'slug' => Tenant::generateUniqueSlug('Existing Tenant'),
    ]);

    $player = TenantPlayer::create([
        'tenant_id' => $tenant->id,
        'display_name' => 'Existing Player',
        'steam_id' => '76561198000000002',
    ]);

    $ticket = TenantSupportTicket::create([
        'tenant_id' => $tenant->id,
        'subject' => 'Stuck loading screen',
        'priority' => TenantSupportTicket::PRIORITY_NORMAL,
        'status' => TenantSupportTicket::STATUS_OPEN,
        'external_reference' => 'ticket-dup',
        'created_by_player_id' => $player->id,
        'opened_at' => Carbon::parse('2025-11-10 10:00:00'),
    ]);

    $plainKey = 'collector-'.Str::random(16);

    TenantApiKey::create([
        'tenant_id' => $tenant->id,
        'name' => 'Game Server',
        'key_type' => TenantApiKey::TYPE_DATA_COLLECTOR,
        'key_hash' => hash('sha256', $plainKey),
        'last_four' => substr($plainKey, -4),
    ]);

    $response = $this->withHeaders(['X-Api-Key' => $plainKey])
        ->postJson('/api/v1/tenant/support-tickets', [
            'subject' => 'Should be ignored',
            'external_reference' => 'ticket-dup',
            'created_by_player_steam_id' => $player->steam_id,
        ]);

    $response->assertOk();
    $response->assertJsonPath('data.id', $ticket->id);
    $response->assertJsonPath('data.subject', 'Stuck loading screen');
});
