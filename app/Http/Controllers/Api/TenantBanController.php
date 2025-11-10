<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantBan;
use App\Models\TenantPlayer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class TenantBanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenant = $this->resolveTenant($request);

        $filters = $request->validate([
            'steam_id' => ['nullable', 'string', 'max:64'],
            'player_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:255'],
            'since' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
            'include_admin_reason' => ['nullable', 'boolean'],
        ]);

        $query = TenantBan::query()
            ->forTenant($tenant)
            ->orderByDesc('banned_at')
            ->orderByDesc('id');

        if (! empty($filters['steam_id'])) {
            $query->where('player_steam_id', trim($filters['steam_id']));
        }

        if (! empty($filters['player_id'])) {
            $query->where('tenant_player_id', (int) $filters['player_id']);
        }

        if (! empty($filters['search'])) {
            $search = '%'.trim($filters['search']).'%';
            $query->where(function ($sub) use ($search) {
                $sub->where('player_name', 'like', $search)
                    ->orWhere('player_steam_id', 'like', $search)
                    ->orWhere('reason', 'like', $search)
                    ->orWhere('admin_reason', 'like', $search);
            });
        }

        if (! empty($filters['since'])) {
            $since = Carbon::parse($filters['since']);
            $query->where(function ($sub) use ($since) {
                $sub->whereNotNull('banned_at')->where('banned_at', '>=', $since)
                    ->orWhere('created_at', '>=', $since);
            });
        }

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 50;
        $page = isset($filters['page']) ? (int) $filters['page'] : 1;

        $perPage = max(1, min(200, $perPage));
        $page = max(1, $page);

        $total = (clone $query)->count();

        $bans = $query
            ->forPage($page, $perPage)
            ->with([
                'player:id,tenant_id,display_name,steam_id',
                'createdByUser:id,name',
                'createdByContact:id,name',
            ])
            ->get();

    $includeAdminReason = $this->shouldIncludeAdminReason($request->query('include_admin_reason'));

        return new JsonResponse([
            'data' => $bans->map(fn (TenantBan $ban) => $this->transformBan($ban, $includeAdminReason))->values()->all(),
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'has_more' => ($page * $perPage) < $total,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenant = $this->resolveTenant($request);

        $data = $request->validate([
            'player_name' => ['required', 'string', 'max:255'],
            'steam_id' => ['nullable', 'string', 'max:64'],
            'tenant_player_id' => ['nullable', 'integer'],
            'reason' => ['required', 'string', 'max:500'],
            'admin_reason' => ['nullable', 'string', 'max:1000'],
            'banned_at' => ['nullable', 'date'],
        ]);

        $player = null;
        if (! empty($data['tenant_player_id'])) {
            $player = $this->findTenantPlayer($tenant, (int) $data['tenant_player_id']);
        } elseif (! empty($data['steam_id'])) {
            $player = TenantPlayer::query()
                ->where('tenant_id', $tenant->id)
                ->where('steam_id', trim($data['steam_id']))
                ->first();
        }

        $playerName = trim($data['player_name']);
        $steamId = isset($data['steam_id']) && $data['steam_id'] !== '' ? trim($data['steam_id']) : ($player ? $player->steam_id : null);
        $bannedAt = ! empty($data['banned_at']) ? Carbon::parse($data['banned_at']) : Carbon::now();
        $adminReason = isset($data['admin_reason']) && $data['admin_reason'] !== '' ? trim($data['admin_reason']) : null;

        $ban = TenantBan::create([
            'tenant_id' => $tenant->id,
            'tenant_player_id' => $player ? $player->id : null,
            'player_name' => $playerName,
            'player_steam_id' => $steamId,
            'reason' => trim($data['reason']),
            'admin_reason' => $adminReason,
            'banned_at' => $bannedAt,
        ]);

        $ban->loadMissing([
            'player:id,tenant_id,display_name,steam_id',
            'createdByUser:id,name',
            'createdByContact:id,name',
        ]);

        return new JsonResponse([
            'data' => $this->transformBan($ban, true),
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, int $ban): JsonResponse
    {
        $tenant = $this->resolveTenant($request);
        $tenantBan = $this->findTenantBan($tenant, $ban);

    $includeAdminReason = $this->shouldIncludeAdminReason($request->query('include_admin_reason'));

        return new JsonResponse([
            'data' => $this->transformBan($tenantBan, $includeAdminReason),
        ]);
    }

    public function update(Request $request, int $ban): JsonResponse
    {
        $tenant = $this->resolveTenant($request);
        $tenantBan = $this->findTenantBan($tenant, $ban);

        $data = $request->validate([
            'player_name' => ['sometimes', 'string', 'max:255'],
            'reason' => ['sometimes', 'string', 'max:500'],
            'admin_reason' => ['nullable', 'string', 'max:1000'],
            'banned_at' => ['nullable', 'date'],
        ]);

        if (array_key_exists('player_name', $data)) {
            $tenantBan->player_name = trim($data['player_name']);
        }

        if (array_key_exists('reason', $data)) {
            $tenantBan->reason = trim($data['reason']);
        }

        if (array_key_exists('admin_reason', $data)) {
            $tenantBan->admin_reason = $data['admin_reason'] !== null && $data['admin_reason'] !== ''
                ? trim((string) $data['admin_reason'])
                : null;
        }

        if (array_key_exists('banned_at', $data)) {
            $tenantBan->banned_at = $data['banned_at'] ? Carbon::parse($data['banned_at']) : null;
        }

        $tenantBan->save();
        $tenantBan->loadMissing([
            'player:id,tenant_id,display_name,steam_id',
            'createdByUser:id,name',
            'createdByContact:id,name',
        ]);

    $includeAdminReason = $this->shouldIncludeAdminReason($request->query('include_admin_reason'));

        return new JsonResponse([
            'data' => $this->transformBan($tenantBan, $includeAdminReason),
        ]);
    }

    public function destroy(Request $request, int $ban): JsonResponse
    {
        $tenant = $this->resolveTenant($request);
        $tenantBan = $this->findTenantBan($tenant, $ban);

        $tenantBan->delete();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    private function resolveTenant(Request $request): Tenant
    {
        /** @var Tenant|null $tenant */
        $tenant = $request->attributes->get('tenant');

        abort_unless($tenant instanceof Tenant, Response::HTTP_INTERNAL_SERVER_ERROR, 'Tenant context missing.');

        return $tenant;
    }

    private function findTenantBan(Tenant $tenant, int $banId): TenantBan
    {
        $ban = TenantBan::query()
            ->forTenant($tenant)
            ->whereKey($banId)
            ->with([
                'player:id,tenant_id,display_name,steam_id',
                'createdByUser:id,name',
                'createdByContact:id,name',
            ])
            ->first();

        abort_unless($ban instanceof TenantBan, Response::HTTP_NOT_FOUND, 'Ban not found.');

        return $ban;
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

    private function transformBan(TenantBan $ban, bool $includeAdminReason): array
    {
        return [
            'id' => $ban->id,
            'tenant_id' => $ban->tenant_id,
            'tenant_player_id' => $ban->tenant_player_id,
            'player_name' => $ban->player_name,
            'player_steam_id' => $ban->player_steam_id,
            'reason' => $ban->reason,
            'admin_reason' => $includeAdminReason ? $ban->admin_reason : null,
            'banned_at' => $ban->banned_at ? $ban->banned_at->toIso8601String() : null,
            'created_at' => $ban->created_at ? $ban->created_at->toIso8601String() : null,
            'updated_at' => $ban->updated_at ? $ban->updated_at->toIso8601String() : null,
            'banning_admin' => [
                'user_id' => $ban->created_by_user_id,
                'user_name' => $ban->createdByUser->name ?? null,
                'contact_id' => $ban->created_by_contact_id,
                'contact_name' => $ban->createdByContact->name ?? null,
                'label' => $ban->banningAdminLabel(),
            ],
            'player' => $ban->player ? [
                'id' => $ban->player->id,
                'display_name' => $ban->player->display_name,
                'steam_id' => $ban->player->steam_id,
            ] : null,
        ];
    }

    private function shouldIncludeAdminReason($value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
