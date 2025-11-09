<?php

namespace App\Support;

use App\Models\User;
use function collect;

class TenantAccessManager
{
    /**
     * Persist the list of tenant access options in the session.
     *
     * @param array<int, array{id:int, name:string, type?:string, tenant_contact_id?:int|null, tenant_player_id?:int|null, roles?:array<int, string>, player_note?:string|null}> $options
     */
    public static function storeOptions(\Illuminate\Http\Request $request, array $options): void
    {
        $normalized = collect($options)
            ->map(function (array $option): array {
                $roles = array_values(array_filter($option['roles'] ?? [], static fn ($role) => is_string($role) && $role !== ''));

                return [
                    'id' => (int) ($option['id'] ?? 0),
                    'name' => (string) ($option['name'] ?? ''),
                    'type' => $option['type'] ?? 'contact',
                    'tenant_contact_id' => isset($option['tenant_contact_id']) ? (int) $option['tenant_contact_id'] : null,
                    'tenant_player_id' => isset($option['tenant_player_id']) ? (int) $option['tenant_player_id'] : null,
                    'roles' => $roles,
                    'player_note' => $option['player_note'] ?? null,
                ];
            })
            ->filter(fn (array $option): bool => $option['id'] > 0)
            ->unique('id')
            ->values()
            ->all();

        $request->session()->put('tenant_access_options', $normalized);
    }

    /**
     * Activate a tenant selection for the current session and update user context.
     *
     * @param array{id:int, name:string, type?:string, tenant_contact_id?:int|null, tenant_player_id?:int|null} $selection
     */
    public static function activateSelection(\Illuminate\Http\Request $request, ?User $user, array $selection): void
    {
        $tenantId = (int) ($selection['id'] ?? 0);

        if ($tenantId <= 0) {
            return;
        }

        $request->session()->put('tenant_id', $tenantId);

        if (! empty($selection['tenant_contact_id'])) {
            $contactId = (int) $selection['tenant_contact_id'];
            $request->session()->put('active_contact_id', $contactId);

            if ($user && (int) $user->tenant_contact_id !== $contactId) {
                $user->tenant_contact_id = $contactId;
                $user->save();
            }
        } else {
            $request->session()->forget('active_contact_id');

            if ($user && $user->tenant_contact_id) {
                $user->tenant_contact_id = null;
                $user->save();
            }
        }

        if (! empty($selection['tenant_player_id'])) {
            $request->session()->put('active_player_id', (int) $selection['tenant_player_id']);
        } else {
            $request->session()->forget('active_player_id');
        }
    }

    /**
     * Retrieve the stored tenant options for the current session.
     */
    public static function options(\Illuminate\Http\Request $request): \Illuminate\Support\Collection
    {
        return collect($request->session()->get('tenant_access_options', []));
    }

    /**
     * List the tenant IDs available to the current session.
     */
    public static function allowedTenantIds(\Illuminate\Http\Request $request): \Illuminate\Support\Collection
    {
        return self::options($request)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();
    }
}
