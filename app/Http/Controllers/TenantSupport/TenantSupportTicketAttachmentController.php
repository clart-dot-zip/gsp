<?php

namespace App\Http\Controllers\TenantSupport;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantContact;
use App\Models\TenantSupportTicket;
use App\Models\TenantSupportTicketAttachment;
use App\Models\TenantSupportTicketNote;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TenantSupportTicketAttachmentController extends Controller
{
    /**
     * Delete an attachment from a ticket note.
     */
    public function destroy(
        Request $request,
        Tenant $tenant,
        TenantSupportTicket $ticket,
        TenantSupportTicketAttachment $attachment
    ): RedirectResponse {
        $this->ensureAttachmentBelongsToTicket($tenant, $ticket, $attachment);

        $user = $request->user();

        if (! $this->canModifyAttachment($user, $tenant, $attachment)) {
            abort(403);
        }

        if ($attachment->disk && $attachment->path) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        }

        $attachment->delete();

        return redirect()
            ->route('tenants.pages.show', ['page' => 'support_tickets', 'highlight_ticket' => $ticket->id])
            ->with('status', 'Attachment removed.');
    }

    /**
     * Ensure the attachment belongs to the tenant ticket.
     */
    protected function ensureAttachmentBelongsToTicket(
        Tenant $tenant,
        TenantSupportTicket $ticket,
        TenantSupportTicketAttachment $attachment
    ): void {
        if ((int) $ticket->tenant_id !== (int) $tenant->id) {
            abort(404);
        }

        if ((int) $attachment->tenant_id !== (int) $tenant->id) {
            abort(404);
        }

        $note = $attachment->note;

        if (! $note instanceof TenantSupportTicketNote || (int) $note->tenant_support_ticket_id !== (int) $ticket->id) {
            abort(404);
        }
    }

    /**
     * Determine whether the user can modify the attachment.
     */
    protected function canModifyAttachment($user, Tenant $tenant, TenantSupportTicketAttachment $attachment): bool
    {
        if ($user instanceof User && $user->hasPermission('manage_support_tickets')) {
            return true;
        }

        $note = $attachment->note;

        if (! $note instanceof TenantSupportTicketNote) {
            return false;
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
