<?php

namespace App\Http\Controllers\TenantSupport;

use App\Http\Controllers\Controller;
use App\Http\Requests\TenantSupport\TenantSupportTicketClaimRequest;
use App\Http\Requests\TenantSupport\TenantSupportTicketStoreRequest;
use App\Http\Requests\TenantSupport\TenantSupportTicketUpdateRequest;
use App\Models\Tenant;
use App\Models\TenantContact;
use App\Models\TenantSupportTicket;
use App\Models\User;
use App\Services\TenantSupport\TicketNoteManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantSupportTicketController extends Controller
{
    /**
     * @var TicketNoteManager
     */
    protected $noteManager;

    public function __construct(TicketNoteManager $noteManager)
    {
        $this->noteManager = $noteManager;
    }

    /**
     * Store a newly created support ticket.
     */
    public function store(TenantSupportTicketStoreRequest $request, Tenant $tenant): RedirectResponse
    {
        $user = $request->user();
        $contact = $request->actingTenantContact();
        $disk = Config::get('filesystems.default', 'public');

        $ticket = DB::transaction(function () use ($request, $tenant, $user, $contact, $disk) {
            $ticket = new TenantSupportTicket([
                'tenant_id' => $tenant->id,
                'subject' => $request->input('subject'),
                'description' => $request->input('description'),
                'priority' => $request->input('priority', TenantSupportTicket::PRIORITY_NORMAL),
                'status' => TenantSupportTicket::STATUS_OPEN,
                'opened_at' => Carbon::now(),
            ]);

            if ($user instanceof User && ! $user->isTenantContact()) {
                $ticket->created_by_user_id = $user->id;
            } elseif ($contact instanceof TenantContact) {
                $ticket->created_by_contact_id = $contact->id;
            }

            $ticket->save();

            $playerIds = $request->normalizedPlayerIds();
            if (! empty($playerIds)) {
                $ticket->players()->syncWithPivotValues($playerIds, [
                    'tenant_id' => $tenant->id,
                ]);
            }

            $assignees = $request->normalizedAssignees();
            if ($assignees->isNotEmpty()) {
                $ticket->syncAssignees($assignees);
            }

            $notePayload = $request->notePayload();
            $uploadedFiles = $request->file('attachments', []);

            if ($notePayload || ! empty($uploadedFiles)) {
                $this->noteManager->createNoteWithAttachments(
                    $ticket,
                    $notePayload ?? [],
                    $user instanceof User ? $user : null,
                    $contact,
                    (array) $uploadedFiles,
                    $disk
                );
            }

            return $ticket;
        });

        return redirect()
            ->route('tenants.pages.show', ['page' => 'support_tickets', 'highlight_ticket' => $ticket->id])
            ->with('status', 'Support ticket created successfully.');
    }

    /**
     * Update a support ticket.
     */
    public function update(TenantSupportTicketUpdateRequest $request, Tenant $tenant, TenantSupportTicket $ticket): RedirectResponse
    {
        $this->ensureTicketBelongsToTenant($tenant, $ticket);

        DB::transaction(function () use ($request, $ticket) {
            if ($request->filled('subject')) {
                $ticket->subject = $request->input('subject');
            }

            if ($request->exists('description')) {
                $ticket->description = $request->input('description');
            }

            $newStatus = $request->input('status', TenantSupportTicket::STATUS_OPEN);
            $newPriority = $request->input('priority', TenantSupportTicket::PRIORITY_NORMAL);

            $ticket->status = $newStatus;
            $ticket->priority = $newPriority;

            $this->synchroniseStatusTimestamps($ticket, $newStatus);

            $ticket->save();

            $playerIds = $request->normalizedPlayerIds();
            $ticket->players()->syncWithPivotValues($playerIds, [
                'tenant_id' => $ticket->tenant_id,
            ]);

            $ticket->syncAssignees($request->normalizedAssignees());
        });

        return redirect()
            ->route('tenants.pages.show', ['page' => 'support_tickets', 'highlight_ticket' => $ticket->id])
            ->with('status', 'Support ticket updated.');
    }

    /**
     * Allow the current user to claim a ticket.
     */
    public function claim(TenantSupportTicketClaimRequest $request, Tenant $tenant, TenantSupportTicket $ticket): RedirectResponse
    {
        $this->ensureTicketBelongsToTenant($tenant, $ticket);

        $user = $request->user();

        if (! $ticket->canBeClaimedBy($user)) {
            abort(403);
        }

        [$assigneeType, $assigneeId] = $this->resolveAssigneeTuple($user);

        $ticket->assignees()->updateOrCreate(
            [
                'assignee_type' => $assigneeType,
                'assignee_id' => $assigneeId,
            ],
            [
                'tenant_id' => $ticket->tenant_id,
                'assigned_at' => Carbon::now(),
            ]
        );

        return redirect()
            ->route('tenants.pages.show', ['page' => 'support_tickets', 'highlight_ticket' => $ticket->id])
            ->with('status', 'Ticket claimed successfully.');
    }

    /**
     * Release the current user from a ticket claim.
     */
    public function release(TenantSupportTicketClaimRequest $request, Tenant $tenant, TenantSupportTicket $ticket): RedirectResponse
    {
        $this->ensureTicketBelongsToTenant($tenant, $ticket);

        $user = $request->user();

        if (! $ticket->canBeClaimedBy($user)) {
            abort(403);
        }

        [$assigneeType, $assigneeId] = $this->resolveAssigneeTuple($user);

        $ticket->assignees()
            ->where('assignee_type', $assigneeType)
            ->where('assignee_id', $assigneeId)
            ->delete();

        return redirect()
            ->route('tenants.pages.show', ['page' => 'support_tickets', 'highlight_ticket' => $ticket->id])
            ->with('status', 'Ticket unclaimed.');
    }

    /**
     * Ensure the ticket belongs to the supplied tenant.
     */
    protected function ensureTicketBelongsToTenant(Tenant $tenant, TenantSupportTicket $ticket): void
    {
        if ((int) $ticket->tenant_id !== (int) $tenant->id) {
            abort(404);
        }
    }

    /**
     * Update timestamp fields for status transitions.
     */
    protected function synchroniseStatusTimestamps(TenantSupportTicket $ticket, string $status): void
    {
        if ($status === TenantSupportTicket::STATUS_OPEN) {
            $ticket->resolved_at = null;
            $ticket->closed_at = null;

            return;
        }

        if ($status === TenantSupportTicket::STATUS_IN_PROGRESS) {
            $ticket->resolved_at = null;
            $ticket->closed_at = null;

            return;
        }

        if ($status === TenantSupportTicket::STATUS_RESOLVED) {
            if (is_null($ticket->resolved_at)) {
                $ticket->resolved_at = Carbon::now();
            }

            $ticket->closed_at = null;

            return;
        }

        if ($status === TenantSupportTicket::STATUS_CLOSED) {
            if (is_null($ticket->resolved_at)) {
                $ticket->resolved_at = Carbon::now();
            }

            if (is_null($ticket->closed_at)) {
                $ticket->closed_at = Carbon::now();
            }
        }
    }

    /**
     * Resolve the assignee tuple for the authenticated user.
     */
    protected function resolveAssigneeTuple(?User $user): array
    {
        if (! $user instanceof User) {
            abort(403);
        }

        if ($user->isTenantContact() && $user->tenantContact) {
            return [TenantContact::class, $user->tenantContact->id];
        }

        return [User::class, $user->id];
    }
}
