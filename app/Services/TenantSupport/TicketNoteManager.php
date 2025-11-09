<?php

namespace App\Services\TenantSupport;

use App\Models\TenantContact;
use App\Models\TenantSupportTicket;
use App\Models\TenantSupportTicketNote;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class TicketNoteManager
{
    /**
     * Create a ticket note and process attachments.
     *
     * @param array<int, UploadedFile> $attachments
     */
    public function createNoteWithAttachments(
        TenantSupportTicket $ticket,
        array $payload,
        ?User $user,
        ?TenantContact $contact,
        array $attachments = [],
        ?string $disk = null
    ): TenantSupportTicketNote {
        $note = $this->createNote($ticket, $payload, $user, $contact);

        if (! empty($attachments)) {
            $this->storeAttachments($note, $attachments, $disk);
        }

        return $note;
    }

    /**
     * Persist the note for the ticket.
     */
    public function createNote(
        TenantSupportTicket $ticket,
        array $payload,
        ?User $user,
        ?TenantContact $contact
    ): TenantSupportTicketNote {
        $note = new TenantSupportTicketNote([
            'tenant_id' => $ticket->tenant_id,
            'tenant_support_ticket_id' => $ticket->id,
            'body' => $payload['body'] ?? null,
            'is_resolution' => (bool) ($payload['is_resolution'] ?? false),
            'timer_seconds' => $payload['timer_seconds'] ?? null,
            'timer_started_at' => $this->parseNullableDate($payload['timer_started_at'] ?? null),
            'timer_stopped_at' => $this->parseNullableDate($payload['timer_stopped_at'] ?? null),
        ]);

        if ($user instanceof User && ! $user->isTenantContact()) {
            $note->author()->associate($user);
        } elseif ($contact instanceof TenantContact) {
            $note->author()->associate($contact);
        }

        $note->save();

        return $note;
    }

    /**
     * Store attachments for a note.
     *
     * @param array<int, UploadedFile> $attachments
     */
    public function storeAttachments(
        TenantSupportTicketNote $note,
        array $attachments,
        ?string $disk = null
    ): void {
        $diskName = $disk ?: Config::get('filesystems.default', 'public');
        $filesystem = Storage::disk($diskName);

        foreach ($attachments as $attachment) {
            if (! $attachment instanceof UploadedFile) {
                continue;
            }

            $path = $this->storeAttachmentFile($filesystem, $attachment, $note);

            $note->attachments()->create([
                'tenant_id' => $note->tenant_id,
                'disk' => $diskName,
                'path' => $path,
                'original_name' => $attachment->getClientOriginalName(),
                'mime_type' => $attachment->getClientMimeType(),
                'size' => $attachment->getSize() ?: 0,
            ]);
        }
    }

    /**
     * Store a single attachment file.
     */
    protected function storeAttachmentFile(
        Filesystem $filesystem,
        UploadedFile $attachment,
        TenantSupportTicketNote $note
    ): string {
        $pathPrefix = sprintf('support-tickets/%d/%d', $note->tenant_id, $note->tenant_support_ticket_id);

        return $filesystem->putFile($pathPrefix, $attachment);
    }

    /**
     * Parse nullable date strings safely.
     */
    protected function parseNullableDate($value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
