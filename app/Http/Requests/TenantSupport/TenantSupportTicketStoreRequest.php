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
        return $this->authorizeForTenant();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $tenant = $this->tenant();
        $tenantId = $tenant instanceof Tenant ? (int) $tenant->id : 0;

        return [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', 'string', Rule::in([
                TenantSupportTicket::PRIORITY_LOW,
                TenantSupportTicket::PRIORITY_NORMAL,
                TenantSupportTicket::PRIORITY_HIGH,
                TenantSupportTicket::PRIORITY_CRITICAL,
            ])],
            'assignees' => ['nullable', 'array'],
            'assignees.*' => ['string'],
            'players' => ['nullable', 'array'],
            'players.*' => ['integer', Rule::exists('tenant_players', 'id')->where('tenant_id', $tenantId)],
            'note_body' => ['nullable', 'string'],
            'note_is_resolution' => ['nullable', 'boolean'],
            'note_timer_seconds' => ['nullable', 'integer', 'min:0'],
            'note_timer_started_at' => ['nullable', 'date'],
            'note_timer_stopped_at' => ['nullable', 'date', 'after_or_equal:note_timer_started_at'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'image', 'max:5120'],
        ];
    }

    /**
     * Provide note payload array if available.
     */
    public function notePayload(): ?array
    {
        $payload = [
            'body' => $this->input('note_body'),
            'is_resolution' => $this->boolean('note_is_resolution'),
            'timer_seconds' => $this->input('note_timer_seconds'),
            'timer_started_at' => $this->input('note_timer_started_at'),
            'timer_stopped_at' => $this->input('note_timer_stopped_at'),
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
        if (! $this->filled('priority')) {
            $this->merge([
                'priority' => TenantSupportTicket::PRIORITY_NORMAL,
            ]);
        }

        if ($this->filled('note_timer_seconds')) {
            $minutes = (int) $this->input('note_timer_seconds');
            $this->merge([
                'note_timer_seconds' => max(0, $minutes) * 60,
            ]);
        }
    }
}
