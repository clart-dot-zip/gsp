<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantGroup;
use App\Models\TenantPlayer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class TenantPlayerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenant = $this->resolveTenant($request);

        $players = TenantPlayer::query()
            ->where('tenant_id', $tenant->id)
            ->with(['groups:id,name,slug'])
            ->orderBy('display_name')
            ->get()
            ->map(fn (TenantPlayer $player) => $this->transformPlayer($player));

        return new JsonResponse(['data' => $players]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenant = $this->resolveTenant($request);

        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'steam_id' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('tenant_players', 'steam_id')->where('tenant_id', $tenant->id),
            ],
            'avatar_url' => ['nullable', 'url', 'max:255'],
            'last_synced_at' => ['nullable', 'date'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'exists:tenant_groups,id'],
        ]);

        $player = TenantPlayer::create([
            'tenant_id' => $tenant->id,
            'display_name' => $data['display_name'],
            'steam_id' => $this->normalizeNullableString($data['steam_id'] ?? null),
            'avatar_url' => $this->normalizeNullableString($data['avatar_url'] ?? null),
            'last_synced_at' => $this->normalizeTimestamp($data['last_synced_at'] ?? null),
        ]);

        $groupIds = $this->filterTenantGroupIds($tenant, Arr::get($data, 'group_ids', []));
        if ($groupIds !== []) {
            $player->groups()->sync($groupIds);
        }

        $player->load('groups:id,name,slug');

        return new JsonResponse(['data' => $this->transformPlayer($player)], Response::HTTP_CREATED);
    }

    public function show(Request $request, int $player): JsonResponse
    {
        $tenant = $this->resolveTenant($request);
        $tenantPlayer = $this->findTenantPlayer($tenant, $player);

        $tenantPlayer->load('groups:id,name,slug');

        return new JsonResponse(['data' => $this->transformPlayer($tenantPlayer)]);
    }

    public function update(Request $request, int $player): JsonResponse
    {
        $tenant = $this->resolveTenant($request);
        $tenantPlayer = $this->findTenantPlayer($tenant, $player);

        $data = $request->validate([
            'display_name' => ['sometimes', 'string', 'max:255'],
            'steam_id' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('tenant_players', 'steam_id')
                    ->where('tenant_id', $tenant->id)
                    ->ignore($tenantPlayer->id),
            ],
            'avatar_url' => ['nullable', 'url', 'max:255'],
            'last_synced_at' => ['nullable', 'date'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'exists:tenant_groups,id'],
        ]);

        if (array_key_exists('display_name', $data)) {
            $tenantPlayer->display_name = $data['display_name'];
        }

        if (array_key_exists('steam_id', $data)) {
            $tenantPlayer->steam_id = $this->normalizeNullableString($data['steam_id']);
        }

        if (array_key_exists('avatar_url', $data)) {
            $tenantPlayer->avatar_url = $this->normalizeNullableString($data['avatar_url']);
        }

        if (array_key_exists('last_synced_at', $data)) {
            $tenantPlayer->last_synced_at = $this->normalizeTimestamp($data['last_synced_at']);
        }

        $tenantPlayer->save();

        if (array_key_exists('group_ids', $data)) {
            $groupIds = $this->filterTenantGroupIds($tenant, $data['group_ids'] ?? []);
            $tenantPlayer->groups()->sync($groupIds);
        }

        $tenantPlayer->load('groups:id,name,slug');

        return new JsonResponse(['data' => $this->transformPlayer($tenantPlayer)]);
    }

    public function destroy(Request $request, int $player): JsonResponse
    {
        $tenant = $this->resolveTenant($request);
        $tenantPlayer = $this->findTenantPlayer($tenant, $player);

        $tenantPlayer->delete();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    private function resolveTenant(Request $request): Tenant
    {
        /** @var Tenant|null $tenant */
        $tenant = $request->attributes->get('tenant');

        abort_unless($tenant instanceof Tenant, Response::HTTP_INTERNAL_SERVER_ERROR, 'Tenant context missing.');

        return $tenant;
    }

    private function findTenantPlayer(Tenant $tenant, int $playerId): TenantPlayer
    {
        $player = TenantPlayer::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($playerId)
            ->first();

        abort_unless($player instanceof TenantPlayer, Response::HTTP_NOT_FOUND, 'Player not found.');

        return $player;
    }

    private function filterTenantGroupIds(Tenant $tenant, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return TenantGroup::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->values()
            ->all();
    }

    private function normalizeNullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function normalizeTimestamp($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value);
    }

    private function transformPlayer(TenantPlayer $player): array
    {
        $player->loadMissing('groups:id,name,slug');

        return [
            'id' => $player->id,
            'tenant_id' => $player->tenant_id,
            'display_name' => $player->display_name,
            'steam_id' => $player->steam_id,
            'avatar_url' => $player->avatar_url,
            'last_synced_at' => $player->last_synced_at ? $player->last_synced_at->toIso8601String() : null,
            'group_ids' => $player->groups->pluck('id')->values()->all(),
            'groups' => $player->groups->map(static function (TenantGroup $group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'slug' => $group->slug,
                ];
            })->values()->all(),
        ];
    }
}
