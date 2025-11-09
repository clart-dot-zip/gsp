<?php

use App\Models\Group;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\TenantContact;
use App\Models\TenantPlayer;
use App\Models\TenantSupportTicket;
use App\Models\TenantSupportTicketAssignee;
use App\Models\TenantSupportTicketNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use function collect;

uses(RefreshDatabase::class);

function createSupportAdminUser(): User
{
    $permissionSlugs = [
        'view_tenant_pages' => 'View Tenant Pages',
        'manage_support_tickets' => 'Manage Support Tickets',
        'support_tickets_create' => 'Create Support Tickets',
        'support_tickets_comment' => 'Comment on Support Tickets',
        'support_tickets_attach' => 'Upload Support Ticket Attachments',
        'support_tickets_collaborate' => 'Collaborate on Support Tickets',
        'view_tenant_page_support_tickets' => 'View Tenant Page: Staff Help Requests',
    ];

    $permissionIds = collect($permissionSlugs)->mapWithKeys(function ($name, $slug) {
        $permission = Permission::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name]
        );

        return [$slug => $permission->id];
    });

    $group = Group::firstOrCreate([
        'slug' => 'administrators',
    ], [
        'name' => 'Administrators',
    ]);

    $group->permissions()->syncWithoutDetaching($permissionIds->values()->all());

    $user = User::factory()->create();
    $user->groups()->sync([$group->id]);

    return $user;
}

it('allows administrators to create support tickets with notes, players, and attachments', function () {
    Storage::fake('public');

    $user = createSupportAdminUser();
    $tenant = Tenant::create([
        'name' => 'Test Tenant',
        'slug' => 'test-tenant',
    ]);

    $contact = TenantContact::create([
        'tenant_id' => $tenant->id,
        'name' => 'Tenant Owner',
        'email' => 'contact@example.com',
    ]);

    $player = TenantPlayer::create([
        'tenant_id' => $tenant->id,
        'display_name' => 'Support Player',
    ]);

    $response = $this->actingAs($user)
        ->post(route('tenants.support.tickets.store', ['tenant' => $tenant]), [
            'subject' => 'Server crash during event',
            'priority' => 'high',
            'description' => 'Crash report attached.\n- Crash occurred after map change\n- Multiple staff impacted',
            'assignees' => ['contact:'.$contact->id],
            'players' => [$player->id],
            'note_body' => 'Initial ticket created by web portal.',
            'note_timer_seconds' => 15,
            'attachments' => [UploadedFile::fake()->image('crash.png')],
        ]);

    $ticket = TenantSupportTicket::first();
    expect($ticket)->not()->toBeNull();

    $response->assertRedirect(route('tenants.pages.show', ['page' => 'support_tickets', 'highlight_ticket' => $ticket->id]));

    expect($ticket)
        ->and($ticket->subject)->toBe('Server crash during event')
        ->and($ticket->priority)->toBe('high')
        ->and($ticket->status)->toBe(TenantSupportTicket::STATUS_OPEN)
        ->and($ticket->players->pluck('id')->all())->toContain($player->id);

    $assignment = TenantSupportTicketAssignee::first();
    expect($assignment)
        ->not()->toBeNull()
        ->and($assignment->assignee_type)->toBe(TenantContact::class)
        ->and($assignment->assignee_id)->toBe($contact->id);

    $note = TenantSupportTicketNote::first();
    expect($note)
        ->not()->toBeNull()
        ->and($note->tenant_support_ticket_id)->toBe($ticket->id)
        ->and($note->timer_seconds)->toBe(900);

    $attachment = $note->attachments->first();
    expect($attachment)
        ->not()->toBeNull();
    Storage::disk('public')->assertExists($attachment->path);
});

it('allows administrators to claim and release support tickets', function () {
    $user = createSupportAdminUser();
    $tenant = Tenant::create([
        'name' => 'Claim Tenant',
        'slug' => 'claim-tenant',
    ]);

    $ticket = TenantSupportTicket::create([
        'tenant_id' => $tenant->id,
        'subject' => 'Investigate lag spikes',
        'priority' => TenantSupportTicket::PRIORITY_NORMAL,
        'status' => TenantSupportTicket::STATUS_OPEN,
        'opened_at' => Carbon::now(),
    ]);

    $claimResponse = $this->actingAs($user)
        ->post(route('tenants.support.tickets.claim', ['tenant' => $tenant, 'ticket' => $ticket]));
    $claimResponse->assertRedirect();

    expect($ticket->fresh()->assignees()->where('assignee_id', $user->id)->where('assignee_type', User::class)->exists())->toBeTrue();

    $releaseResponse = $this->actingAs($user)
        ->delete(route('tenants.support.tickets.release', ['tenant' => $tenant, 'ticket' => $ticket]));
    $releaseResponse->assertRedirect();

    expect($ticket->fresh()->assignees()->where('assignee_id', $user->id)->where('assignee_type', User::class)->exists())->toBeFalse();
});
