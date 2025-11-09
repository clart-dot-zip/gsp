<?php

namespace App\Http\Requests\TenantSupport;

class TenantSupportTicketClaimRequest extends TenantSupportTicketRequest
{
    public function authorize(): bool
    {
        return $this->authorizeForTenant();
    }

    public function rules(): array
    {
        return [];
    }
}
