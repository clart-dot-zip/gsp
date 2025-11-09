<?php

namespace App\Http\Requests\TenantSupport;

use App\Models\Tenant;
use App\Models\TenantContact;
use App\Models\TenantSupportTicket;
use App\Models\User;
use App\Support\TenantAccessManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use function collect;

/**
 * @mixin \Illuminate\Http\Request
 */
abstract class TenantSupportTicketRequest extends FormRequest
{
    /**
     * Ensure the authenticated user has access to the tenant support workspace.
     */
    protected function authorizeForTenant(): bool
    {
        $user = $this->user();
        $tenant = $this->tenant();

        if (! $user instanceof User || ! $tenant instanceof Tenant) {
            return false;
        }

        if ($user->hasPermission('manage_support_tickets')) {
            return true;
        }

        /** @var \Illuminate\Http\Request $baseRequest */
        $baseRequest = $this;

        $allowedTenantIds = TenantAccessManager::allowedTenantIds($baseRequest);
        if ($allowedTenantIds->isNotEmpty()) {
            return $allowedTenantIds->contains((int) $tenant->id);
        }

        if ($user->isTenantContact() && $user->tenantContact) {
            return (int) $user->tenantContact->tenant_id === (int) $tenant->id;
        }

        return false;
    }

    /**
     * Resolve the tenant instance from the current route.
     */
    public function tenant(): ?Tenant
    {
        $tenant = $this->route('tenant');

        return $tenant instanceof Tenant ? $tenant : null;
    }

    /**
     * Resolve the ticket instance from the current route.
     */
    public function ticket(): ?TenantSupportTicket
    {
        $ticket = $this->route('ticket');

        return $ticket instanceof TenantSupportTicket ? $ticket : null;
    }

    /**
     * Provide normalised assignee payloads.
     *
     * @return Collection<int, array{type: class-string, id: int}>
     */
    public function normalizedAssignees(): Collection
    {
        $values = $this->input('assignees', []);

        if (! is_array($values)) {
            return collect();
        }

        return collect($values)
            ->filter(fn ($value) => is_string($value) && strpos($value, ':') !== false)
            ->map(function (string $value) {
                [$typeKey, $idValue] = array_pad(explode(':', $value, 2), 2, null);
                if (! is_numeric($idValue)) {
                    return null;
                }

                $id = (int) $idValue;

                if ($typeKey === 'user') {
                    return ['type' => User::class, 'id' => $id];
                }

                if (in_array($typeKey, ['contact', 'tenant_contact'], true)) {
                    return ['type' => TenantContact::class, 'id' => $id];
                }

                return null;
            })
            ->filter()
            ->values();
    }

    /**
     * Provide a list of player identifiers associated with the request.
     *
     * @return array<int, int>
     */
    public function normalizedPlayerIds(): array
    {
        $activePlayerId = (int) $this->session()->get('active_player_id');

        if ($activePlayerId > 0) {
            return [$activePlayerId];
        }

        $players = $this->input('players', []);

        if (! is_array($players)) {
            return [];
        }

        return collect($players)
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Fetch the acting tenant contact when available.
     */
    public function actingTenantContact(): ?TenantContact
    {
        $user = $this->user();

        $activeContactId = (int) $this->session()->get('active_contact_id');

        if ($activeContactId > 0) {
            if ($user instanceof User && $user->tenantContact && (int) $user->tenantContact->id === $activeContactId) {
                return $user->tenantContact;
            }

            return TenantContact::query()->find($activeContactId);
        }

        if ($user instanceof User && $user->tenantContact) {
            return $user->tenantContact;
        }

        return null;
    }
}
