<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantBan;
use App\Models\TenantPlayer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantBanController extends Controller
{
    public function create(Request $request, Tenant $tenant): View
    {
        $this->assertTenantContext($request, $tenant);

        $players = $tenant->players()
            ->orderBy('display_name')
            ->get(['id', 'display_name', 'steam_id']);

        return view('tenants.bans.create', [
            'tenant' => $tenant,
            'players' => $players,
            'canViewAdminReason' => $this->canViewAdminReason($request->user()),
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->assertTenantContext($request, $tenant);

        $data = $request->validate([
            'tenant_player_id' => [
                'required',
                'integer',
                Rule::exists('tenant_players', 'id')->where('tenant_id', $tenant->id),
            ],
            'length_code' => ['required', 'string', 'max:16', 'regex:/^(0|[0-9]+[smhdwy])$/i'],
            'reason' => ['required', 'string', 'max:500'],
            'admin_reason' => ['nullable', 'string', 'max:1000'],
            'banned_at' => ['nullable', 'date'],
        ]);

        $player = TenantPlayer::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($data['tenant_player_id']);

        $user = $request->user();
        $createdByUserId = null;
        $createdByContactId = null;
        $canViewAdminReason = $this->canViewAdminReason($user);

        if ($user instanceof User) {
            if ($user->isTenantContact()) {
                $contact = $user->tenantContact;
                if ($contact && $contact->tenant_id === $tenant->id) {
                    $createdByContactId = $contact->id;
                } else {
                    $createdByUserId = $user->id;
                }
            } else {
                $createdByUserId = $user->id;
            }
        }

        $bannedAt = isset($data['banned_at']) && $data['banned_at']
            ? Carbon::parse($data['banned_at'])
            : Carbon::now();

        $reason = trim($data['reason']);
        $lengthCode = $this->normalizeLengthCode($data['length_code']);
        $adminReason = $canViewAdminReason && isset($data['admin_reason']) && $data['admin_reason'] !== ''
            ? trim($data['admin_reason'])
            : null;

        TenantBan::create([
            'tenant_id' => $tenant->id,
            'tenant_player_id' => $player->id,
            'player_name' => $player->display_name,
            'player_steam_id' => $player->steam_id,
            'length_code' => $lengthCode,
            'reason' => $reason,
            'admin_reason' => $adminReason,
            'created_by_user_id' => $createdByUserId,
            'created_by_contact_id' => $createdByContactId,
            'banned_at' => $bannedAt,
        ]);

        return Redirect::route('tenants.pages.show', ['page' => 'bans'])
            ->with('status', 'Ban added for '.$player->display_name.'.');
    }

    public function edit(Request $request, Tenant $tenant, TenantBan $ban): View
    {
        $this->assertTenantContext($request, $tenant);
        abort_unless($ban->tenant_id === $tenant->id, 404);

        $canViewAdminReason = $this->canViewAdminReason($request->user());

        $bannedAtValue = $ban->banned_at
            ? $ban->banned_at->timezone(config('app.timezone'))->format('Y-m-d\TH:i')
            : ($ban->created_at
                ? $ban->created_at->timezone(config('app.timezone'))->format('Y-m-d\TH:i')
                : now()->format('Y-m-d\TH:i'));

        return view('tenants.bans.edit', [
            'tenant' => $tenant,
            'ban' => $ban,
            'canViewAdminReason' => $canViewAdminReason,
            'bannedAtValue' => $bannedAtValue,
        ]);
    }

    public function update(Request $request, Tenant $tenant, TenantBan $ban): RedirectResponse
    {
        $this->assertTenantContext($request, $tenant);
        abort_unless($ban->tenant_id === $tenant->id, 404);

        $canViewAdminReason = $this->canViewAdminReason($request->user());

        $data = $request->validate([
            'length_code' => ['required', 'string', 'max:16', 'regex:/^(0|[0-9]+[smhdwy])$/i'],
            'reason' => ['required', 'string', 'max:500'],
            'admin_reason' => ['nullable', 'string', 'max:1000'],
            'banned_at' => ['nullable', 'date'],
        ]);

        $ban->length_code = $this->normalizeLengthCode($data['length_code']);
        $ban->reason = trim($data['reason']);

        if ($canViewAdminReason) {
            $ban->admin_reason = isset($data['admin_reason']) && $data['admin_reason'] !== ''
                ? trim($data['admin_reason'])
                : null;
        }

        $ban->banned_at = isset($data['banned_at']) && $data['banned_at'] !== ''
            ? Carbon::parse($data['banned_at'])
            : null;

        $ban->save();

        return Redirect::route('tenants.pages.show', ['page' => 'bans'])
            ->with('status', 'Ban updated.');
    }

    public function destroy(Request $request, Tenant $tenant, TenantBan $ban): RedirectResponse
    {
        $this->assertTenantContext($request, $tenant);
        abort_unless($ban->tenant_id === $tenant->id, 404);

        $ban->delete();

        return Redirect::route('tenants.pages.show', ['page' => 'bans'])
            ->with('status', 'Ban removed.');
    }

    protected function assertTenantContext(Request $request, Tenant $tenant): void
    {
        $selectedTenantId = (int) $request->session()->get('tenant_id');
        abort_unless($selectedTenantId === $tenant->id, 403);
    }

    protected function canViewAdminReason($user): bool
    {
        return $user instanceof User && $user->hasPermission('view_tenant_ban_admin_reason');
    }

    private function normalizeLengthCode(string $code): string
    {
        $trimmed = strtolower(trim($code));

        return $trimmed === '' ? '0' : $trimmed;
    }
}
