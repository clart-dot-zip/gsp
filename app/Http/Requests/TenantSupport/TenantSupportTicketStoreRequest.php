<?php

namespace App\Http\Requests\TenantSupport;

use App\Models\Tenant;
use App\Models\TenantSupportTicket;
use Illuminate\Validation\Rule;

class TenantSupportTicketStoreRequest extends TenantSupportTicketRequest
{
    /**
     * Determine if the user is authorised to make this request.
     */
    public function authorize(): bool
    {
        return $this->authorizeForTenant()
            && $this->userHasSupportPermission('support_tickets_create');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $tenant = $this->tenant();
        $tenantId = $tenant instanceof Tenant ? (int) $tenant->id : 0;
        $isPlayerSession = $this->session()->has('active_player_id');
        $canCollaborate = $this->userHasSupportPermission('support_tickets_collaborate');
        $canAttach = $this->userHasSupportPermission('support_tickets_attach');

        $rules = [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', 'string', Rule::in([
                TenantSupportTicket::PRIORITY_LOW,
                TenantSupportTicket::PRIORITY_NORMAL,
                TenantSupportTicket::PRIORITY_HIGH,
                TenantSupportTicket::PRIORITY_CRITICAL,
            ])],
            'assignees' => $canCollaborate ? ['nullable', 'array'] : ['prohibited'],
            'assignees.*' => $canCollaborate ? ['string'] : ['prohibited'],
            'players' => ['nullable', 'array'],
            'players.*' => ['integer', Rule::exists('tenant_players', 'id')->where('tenant_id', $tenantId)],
            'note_body' => ['nullable', 'string'],
            'note_is_resolution' => $isPlayerSession || ! $canCollaborate ? ['prohibited'] : ['nullable', 'boolean'],
            'note_timer_seconds' => $isPlayerSession || ! $canCollaborate ? ['prohibited'] : ['nullable', 'integer', 'min:0'],
            'note_timer_started_at' => $isPlayerSession || ! $canCollaborate ? ['prohibited'] : ['nullable', 'date'],
            'note_timer_stopped_at' => $isPlayerSession || ! $canCollaborate ? ['prohibited'] : ['nullable', 'date', 'after_or_equal:note_timer_started_at'],
            'attachments' => $canAttach ? ['nullable', 'array'] : ['prohibited'],
            'attachments.*' => $canAttach ? ['file', 'image', 'max:5120'] : ['prohibited'],
        ];

        return $rules;
    }

    /**
     * Provide note payload array if available.
     */
    public function notePayload(): ?array
    {
        $isPlayerSession = $this->session()->has('active_player_id');

        $canCollaborate = $this->userHasSupportPermission('support_tickets_collaborate');

        $payload = [
            'body' => $this->input('note_body'),
            'is_resolution' => ($isPlayerSession || ! $canCollaborate) ? false : $this->boolean('note_is_resolution'),
            'timer_seconds' => ($isPlayerSession || ! $canCollaborate) ? null : $this->input('note_timer_seconds'),
            'timer_started_at' => ($isPlayerSession || ! $canCollaborate) ? null : $this->input('note_timer_started_at'),
            'timer_stopped_at' => ($isPlayerSession || ! $canCollaborate) ? null : $this->input('note_timer_stopped_at'),
        ];

        if (
            empty($payload['body'])
            && ! $this->hasFile('attachments')
            && is_null($payload['timer_seconds'])
            && $payload['is_resolution'] === false
        ) {
            return null;
        }

        return $payload;
    }

    protected function prepareForValidation(): void
    {
        if ($this->session()->has('active_player_id')) {
            return;
        }

        if (! $this->filled('priority')) {
            $this->merge([
                'priority' => TenantSupportTicket::PRIORITY_NORMAL,
            ]);
        }

        if ($this->filled('note_timer_seconds') && $this->userHasSupportPermission('support_tickets_collaborate')) {
            $minutes = (int) $this->input('note_timer_seconds');
            $this->merge([
                'note_timer_seconds' => max(0, $minutes) * 60,
            ]);
        }
    }
}
