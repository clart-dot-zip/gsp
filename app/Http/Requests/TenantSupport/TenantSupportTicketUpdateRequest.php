<?php

namespace App\Http\Requests\TenantSupport;

use App\Models\Tenant;
use App\Models\TenantSupportTicket;
use Illuminate\Validation\Rule;

class TenantSupportTicketUpdateRequest extends TenantSupportTicketRequest
{
    /**
     * Determine if the user is authorised to make this request.
     */
    public function authorize(): bool
    {
        if ($this->session()->has('active_player_id')) {
            return false;
        }

        return $this->authorizeForTenant()
            && $this->userHasSupportPermission('support_tickets_collaborate');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $tenant = $this->tenant();
        $tenantId = $tenant instanceof Tenant ? (int) $tenant->id : 0;

        return [
            'subject' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in([
                TenantSupportTicket::STATUS_OPEN,
                TenantSupportTicket::STATUS_IN_PROGRESS,
                TenantSupportTicket::STATUS_RESOLVED,
                TenantSupportTicket::STATUS_CLOSED,
            ])],
            'priority' => ['required', 'string', Rule::in([
                TenantSupportTicket::PRIORITY_LOW,
                TenantSupportTicket::PRIORITY_NORMAL,
                TenantSupportTicket::PRIORITY_HIGH,
                TenantSupportTicket::PRIORITY_CRITICAL,
            ])],
            'assignees' => ['nullable', 'array'],
            'assignees.*' => ['string'],
            'players' => ['nullable', 'array'],
            'players.*' => ['integer', Rule::exists('tenant_players', 'id')->where('tenant_id', $tenantId)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('priority')) {
            $this->merge([
                'priority' => TenantSupportTicket::PRIORITY_NORMAL,
            ]);
        }

        if (! $this->filled('status')) {
            $this->merge([
                'status' => TenantSupportTicket::STATUS_OPEN,
            ]);
        }
    }
}
