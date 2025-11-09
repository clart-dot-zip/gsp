<?php

namespace App\Http\Requests\TenantSupport;

class TenantSupportTicketClaimRequest extends TenantSupportTicketRequest
{
    public function authorize(): bool
    {
        if ($this->session()->has('active_player_id')) {
            return false;
        }

        return $this->authorizeForTenant();
    }

    public function rules(): array
    {
        return [];
    }
}
