<?php

namespace App\Http\Requests\TenantSupport;

class TenantSupportTicketClaimRequest extends TenantSupportTicketRequest
{
    public function authorize(): bool
    {
        if ($this->session()->has('active_player_id')) {
            return false;
        }

        return $this->authorizeForTenant()
            && $this->userHasSupportPermission('support_tickets_collaborate');
    }

    public function rules(): array
    {
        return [];
    }
}
