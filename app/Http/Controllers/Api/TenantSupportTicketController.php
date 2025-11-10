<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantContact;
use App\Models\TenantPlayer;
use App\Models\TenantSupportTicket;
use App\Models\TenantSupportTicketNote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class TenantSupportTicketController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $tenant = $this->resolveTenant($request);

        $normalisedInput = $request->all();

        if (isset($normalisedInput['priority']) && is_string($normalisedInput['priority'])) {
            $normalisedInput['priority'] = strtolower($normalisedInput['priority']);
        }

        if (isset($normalisedInput['external_reference']) && is_string($normalisedInput['external_reference'])) {
            $normalisedInput['external_reference'] = trim($normalisedInput['external_reference']);
        }

        if (isset($normalisedInput['description']) && is_string($normalisedInput['description'])) {
            $normalisedInput['description'] = trim($normalisedInput['description']);
        }

        $request->merge($normalisedInput);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', 'string', function (string $attribute, $value, $fail) {
                if ($value === null || $value === '') {
                    return;
                }

                $allowed = [
                    TenantSupportTicket::PRIORITY_LOW,
                    TenantSupportTicket::PRIORITY_NORMAL,
                    TenantSupportTicket::PRIORITY_HIGH,
                    TenantSupportTicket::PRIORITY_CRITICAL,
                ];

                if (! in_array($value, $allowed, true)) {
                    $fail('The '.$attribute.' field must be one of: '.implode(', ', $allowed).'.');
                }
            }],
            'external_reference' => ['nullable', 'string', 'max:255'],
            'created_by_player_id' => [
                'nullable',
                'integer',
                Rule::exists('tenant_players', 'id')->where(fn (Builder $query) => $query->where('tenant_id', $tenant->id)),
            ],
            'created_by_player_steam_id' => ['nullable', 'string', 'max:64'],
            'player_ids' => ['nullable', 'array'],
            'player_ids.*' => [
                'integer',
                Rule::exists('tenant_players', 'id')->where(fn (Builder $query) => $query->where('tenant_id', $tenant->id)),
            ],
            'opened_at' => ['nullable', 'date'],
            'note_body' => ['nullable', 'string'],
            'note_is_resolution' => ['nullable', 'boolean'],
            'note_timer_seconds' => ['nullable', 'integer', 'min:0'],
            'note_timer_started_at' => ['nullable', 'date'],
            'note_timer_stopped_at' => ['nullable', 'date', 'after_or_equal:note_timer_started_at'],
            'note_meta' => ['nullable', 'array'],
        ]);

        $externalReference = isset($data['external_reference']) && $data['external_reference'] !== ''
            ? $data['external_reference']
            : null;

        if ($externalReference) {
            $existing = TenantSupportTicket::query()
                ->where('external_reference', $externalReference)
                ->first();

            if ($existing) {
                if ((int) $existing->tenant_id !== (int) $tenant->id) {
                    return new JsonResponse([
                        'message' => 'External reference is already in use by another tenant.',
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                $existing->loadMissing([
                    'createdByUser:id,name',
                    'createdByContact:id,name',
                    'createdByPlayer:id,tenant_id,display_name,steam_id',
                    'latestNote.author',
                    'players:id,tenant_id,display_name,steam_id',
                ]);

                return new JsonResponse([
                    'data' => $this->transformTicket($existing),
                ]);
            }
        }

        $createdByPlayer = null;

        if (! empty($data['created_by_player_id'])) {
            $createdByPlayer = $this->findTenantPlayerById($tenant, (int) $data['created_by_player_id']);
        } elseif (! empty($data['created_by_player_steam_id']) && is_string($data['created_by_player_steam_id'])) {
            $steamId = trim($data['created_by_player_steam_id']);

            if ($steamId !== '') {
                $createdByPlayer = $this->findTenantPlayerBySteamId($tenant, $steamId);
            }
        }

        $rawPlayerIds = isset($data['player_ids']) && is_array($data['player_ids'])
            ? array_filter($data['player_ids'], static fn ($value) => is_numeric($value))
            : [];

        $playerIds = array_values(array_unique(array_map('intval', $rawPlayerIds)));

        if ($createdByPlayer && ! in_array($createdByPlayer->id, $playerIds, true)) {
            $playerIds[] = $createdByPlayer->id;
        }

        $openedAt = ! empty($data['opened_at']) ? Carbon::parse($data['opened_at']) : Carbon::now();
        $priority = isset($data['priority']) && $data['priority'] !== '' ? $data['priority'] : TenantSupportTicket::PRIORITY_NORMAL;
        $description = isset($data['description']) && $data['description'] !== '' ? $data['description'] : null;

        $ticket = TenantSupportTicket::create([
            'tenant_id' => $tenant->id,
            'subject' => trim($data['subject']),
            'description' => $description,
            'priority' => $priority,
            'status' => TenantSupportTicket::STATUS_OPEN,
            'external_reference' => $externalReference,
            'created_by_player_id' => $createdByPlayer ? $createdByPlayer->id : null,
            'opened_at' => $openedAt,
        ]);

        if (! empty($playerIds)) {
            $ticket->players()->syncWithPivotValues($playerIds, [
                'tenant_id' => $tenant->id,
            ]);
        }

        $noteBody = $request->input('note_body');
        $hasNote = is_string($noteBody) && trim($noteBody) !== '';

        if ($hasNote) {
            $notePayload = [
                'body' => $noteBody,
                'is_resolution' => $request->boolean('note_is_resolution', false),
                'timer_seconds' => $request->input('note_timer_seconds'),
                'timer_started_at' => $request->input('note_timer_started_at'),
                'timer_stopped_at' => $request->input('note_timer_stopped_at'),
                'meta' => $request->input('note_meta'),
            ];

            $note = $this->createTicketNote($ticket, $notePayload, $createdByPlayer);
            $ticket->setRelation('latestNote', $note);
        }

        $ticket->loadMissing([
            'createdByUser:id,name',
            'createdByContact:id,name',
            'createdByPlayer:id,tenant_id,display_name,steam_id',
            'latestNote.author',
            'players:id,tenant_id,display_name,steam_id',
        ]);

        return new JsonResponse([
            'data' => $this->transformTicket($ticket),
        ], Response::HTTP_CREATED);
    }

    public function storeNote(Request $request, int $ticket): JsonResponse
    {
        $tenant = $this->resolveTenant($request);
        $supportTicket = $this->findTenantTicket($tenant, $ticket);

        $data = $request->validate([
            'body' => ['required', 'string'],
            'is_resolution' => ['nullable', 'boolean'],
            'author_player_id' => [
                'nullable',
                'integer',
                Rule::exists('tenant_players', 'id')->where(fn ($query) => $query->where('tenant_id', $tenant->id)),
            ],
            'author_player_steam_id' => ['nullable', 'string', 'max:64'],
            'timer_seconds' => ['nullable', 'integer', 'min:0'],
            'timer_started_at' => ['nullable', 'date'],
            'timer_stopped_at' => ['nullable', 'date', 'after_or_equal:timer_started_at'],
            'meta' => ['nullable', 'array'],
        ]);

        $authorPlayer = null;

        if (! empty($data['author_player_id'])) {
            $authorPlayer = $this->findTenantPlayerById($tenant, (int) $data['author_player_id']);
        } elseif (! empty($data['author_player_steam_id']) && is_string($data['author_player_steam_id'])) {
            $steamId = trim($data['author_player_steam_id']);

            if ($steamId !== '') {
                $authorPlayer = $this->findTenantPlayerBySteamId($tenant, $steamId);
            }
        }

        $notePayload = [
            'body' => $data['body'],
            'is_resolution' => $data['is_resolution'] ?? false,
            'timer_seconds' => $data['timer_seconds'] ?? null,
            'timer_started_at' => $data['timer_started_at'] ?? null,
            'timer_stopped_at' => $data['timer_stopped_at'] ?? null,
            'meta' => $data['meta'] ?? null,
        ];

        $note = $this->createTicketNote($supportTicket, $notePayload, $authorPlayer);

        return new JsonResponse([
            'data' => $this->transformNote($note),
        ], Response::HTTP_CREATED);
    }

    private function resolveTenant(Request $request): Tenant
    {
    $tenant = $request->attributes->get('tenant');

    \abort_unless($tenant instanceof Tenant, Response::HTTP_INTERNAL_SERVER_ERROR, 'Tenant context missing.');

        return $tenant;
    }

    private function findTenantTicket(Tenant $tenant, int $ticketId): TenantSupportTicket
    {
        $ticket = TenantSupportTicket::query()
            ->forTenant($tenant)
            ->whereKey($ticketId)
            ->first();

        abort_unless($ticket instanceof TenantSupportTicket, Response::HTTP_NOT_FOUND, 'Support ticket not found.');

        $ticket->loadMissing([
            'createdByUser:id,name',
            'createdByContact:id,name',
            'createdByPlayer:id,tenant_id,display_name,steam_id',
            'latestNote.author',
        ]);

        return $ticket;
    }

    private function findTenantPlayerById(Tenant $tenant, int $playerId): ?TenantPlayer
    {
        return TenantPlayer::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($playerId)
            ->first();
    }

    private function findTenantPlayerBySteamId(Tenant $tenant, string $steamId): ?TenantPlayer
    {
        return TenantPlayer::query()
            ->where('tenant_id', $tenant->id)
            ->where('steam_id', $steamId)
            ->first();
    }

    private function transformTicket(TenantSupportTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'tenant_id' => $ticket->tenant_id,
            'subject' => $ticket->subject,
            'description' => $ticket->description,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'external_reference' => $ticket->external_reference,
            'opened_at' => $ticket->opened_at ? $ticket->opened_at->toIso8601String() : null,
            'resolved_at' => $ticket->resolved_at ? $ticket->resolved_at->toIso8601String() : null,
            'closed_at' => $ticket->closed_at ? $ticket->closed_at->toIso8601String() : null,
            'created_at' => $ticket->created_at ? $ticket->created_at->toIso8601String() : null,
            'updated_at' => $ticket->updated_at ? $ticket->updated_at->toIso8601String() : null,
            'created_by' => [
                'user_id' => $ticket->created_by_user_id,
                'contact_id' => $ticket->created_by_contact_id,
                'player_id' => $ticket->created_by_player_id,
                'player' => $ticket->createdByPlayer ? [
                    'id' => $ticket->createdByPlayer->id,
                    'display_name' => $ticket->createdByPlayer->display_name,
                    'steam_id' => $ticket->createdByPlayer->steam_id,
                ] : null,
            ],
            'players' => $ticket->players
                ->sortBy('id')
                ->values()
                ->map(static fn (TenantPlayer $player) => [
                    'id' => $player->id,
                    'display_name' => $player->display_name,
                    'steam_id' => $player->steam_id,
                ])->all(),
            'latest_note' => $ticket->latestNote ? $this->transformNote($ticket->latestNote) : null,
        ];
    }

    private function transformNote(TenantSupportTicketNote $note): array
    {
        $author = $note->author;
        $authorLabel = null;

        if ($author instanceof User) {
            $authorLabel = $author->name;
        } elseif ($author instanceof TenantContact) {
            $authorLabel = $author->name;
        } elseif ($author instanceof TenantPlayer) {
            $authorLabel = $author->display_name;
        }

        return [
            'id' => $note->id,
            'tenant_id' => $note->tenant_id,
            'ticket_id' => $note->tenant_support_ticket_id,
            'body' => $note->body,
            'is_resolution' => (bool) $note->is_resolution,
            'timer_seconds' => $note->timer_seconds,
            'timer_started_at' => $note->timer_started_at ? $note->timer_started_at->toIso8601String() : null,
            'timer_stopped_at' => $note->timer_stopped_at ? $note->timer_stopped_at->toIso8601String() : null,
            'created_at' => $note->created_at ? $note->created_at->toIso8601String() : null,
            'updated_at' => $note->updated_at ? $note->updated_at->toIso8601String() : null,
            'author' => [
                'type' => $note->author_type,
                'id' => $note->author_id,
                'label' => $authorLabel,
            ],
            'meta' => $note->meta,
        ];
    }

    private function createTicketNote(
        TenantSupportTicket $ticket,
        array $payload,
        ?TenantPlayer $authorPlayer = null
    ): TenantSupportTicketNote {
        $body = isset($payload['body']) ? trim((string) $payload['body']) : null;

        $note = new TenantSupportTicketNote([
            'tenant_id' => $ticket->tenant_id,
            'tenant_support_ticket_id' => $ticket->id,
            'body' => $body !== '' ? $body : null,
            'is_resolution' => (bool) ($payload['is_resolution'] ?? false),
            'timer_seconds' => isset($payload['timer_seconds']) ? max(0, (int) $payload['timer_seconds']) : null,
            'timer_started_at' => $this->parseNullableDate($payload['timer_started_at'] ?? null),
            'timer_stopped_at' => $this->parseNullableDate($payload['timer_stopped_at'] ?? null),
            'meta' => $payload['meta'] ?? null,
        ]);

        if ($authorPlayer instanceof TenantPlayer) {
            $note->author()->associate($authorPlayer);
        }

        $note->save();

        return $note->fresh(['author']);
    }

    private function parseNullableDate($value): ?Carbon
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
