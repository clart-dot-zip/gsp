<?php

namespace App\Http\Controllers\TenantSupport;

use App\Http\Controllers\Controller;
use App\Http\Requests\TenantSupport\TenantSupportTicketNoteStoreRequest;
use App\Models\Tenant;
use App\Models\TenantContact;
use App\Models\TenantSupportTicket;
use App\Models\TenantSupportTicketNote;
use App\Models\User;
use App\Services\TenantSupport\TicketNoteManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TenantSupportTicketNoteController extends Controller
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
     * Store a newly created note for a ticket.
     */
    public function store(
        TenantSupportTicketNoteStoreRequest $request,
        Tenant $tenant,
        TenantSupportTicket $ticket
    ): RedirectResponse {
        $this->ensureTicketBelongsToTenant($tenant, $ticket);

        if (! $request->hasContent()) {
            return redirect()
                ->back()
                ->withErrors(['body' => 'Please add note content, a timer, resolution flag, or at least one attachment.']);
        }

        $user = $request->user();
        $contact = $request->actingTenantContact();
        $disk = Config::get('filesystems.default', 'public');

        DB::transaction(function () use ($request, $ticket, $user, $contact, $disk) {
            $payload = $request->payload();
            $attachments = $request->file('attachments', []);

            $this->noteManager->createNoteWithAttachments(
                $ticket,
                $payload,
                $user instanceof User ? $user : null,
                $contact,
                (array) $attachments,
                $disk
            );
        });

        return redirect()
            ->route('tenants.pages.show', ['page' => 'support_tickets', 'highlight_ticket' => $ticket->id])
            ->with('status', 'Note added to ticket.');
    }

    /**
     * Remove a ticket note and its attachments.
     */
    public function destroy(
        Request $request,
        Tenant $tenant,
        TenantSupportTicket $ticket,
        TenantSupportTicketNote $note
    ): RedirectResponse {
        $this->ensureTicketBelongsToTenant($tenant, $ticket);
        $this->ensureNoteBelongsToTicket($note, $ticket);

        $user = $request->user();

        if (! $this->canDeleteNote($user, $tenant, $note)) {
            abort(403);
        }

        DB::transaction(function () use ($note) {
            $note->loadMissing('attachments');

            foreach ($note->attachments as $attachment) {
                if ($attachment->disk && $attachment->path) {
                    Storage::disk($attachment->disk)->delete($attachment->path);
                }
                $attachment->delete();
            }

            $note->delete();
        });

        return redirect()
            ->route('tenants.pages.show', ['page' => 'support_tickets', 'highlight_ticket' => $ticket->id])
            ->with('status', 'Note removed.');
    }

    /**
     * Ensure the ticket belongs to the tenant.
     */
    protected function ensureTicketBelongsToTenant(Tenant $tenant, TenantSupportTicket $ticket): void
    {
        if ((int) $ticket->tenant_id !== (int) $tenant->id) {
            abort(404);
        }
    }

    /**
     * Ensure the note is related to the ticket.
     */
    protected function ensureNoteBelongsToTicket(TenantSupportTicketNote $note, TenantSupportTicket $ticket): void
    {
        if ((int) $note->tenant_support_ticket_id !== (int) $ticket->id) {
            abort(404);
        }
    }

    /**
     * Check delete permissions for a note.
     */
    protected function canDeleteNote($user, Tenant $tenant, TenantSupportTicketNote $note): bool
    {
        if ($user instanceof User && $user->hasPermission('manage_support_tickets')) {
            return true;
        }

        if ($user instanceof User && $user->isTenantContact()) {
            $contact = $user->tenantContact;

            if ($contact instanceof TenantContact && (int) $contact->tenant_id === (int) $tenant->id) {
                $author = $note->author;

                if ($author instanceof TenantContact) {
                    return (int) $author->id === (int) $contact->id;
                }
            }
        }

        if ($user instanceof User && ! $user->isTenantContact()) {
            $author = $note->author;

            if ($author instanceof User) {
                return (int) $author->id === (int) $user->id;
            }
        }

        return false;
    }
}
