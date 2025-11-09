<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantActivityLog;
use App\Models\TenantSupportTicket;
use App\Models\TenantSupportTicketAssignee;
use App\Models\TenantContact;
use App\Support\TenantAccessManager;
use App\Support\TenantPageAuthorization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class TenantPageController extends Controller
{
    /**
     * Display a tenant contextual page.
     */
    public function show(Request $request, string $page): View|RedirectResponse
    {
        $tenantId = (int) $request->session()->get('tenant_id');

        if ($tenantId === 0) {
            return redirect()->route('tenants.manage')
                ->with('status', 'Please add or choose a tenant to continue.');
        }

        $tenant = Tenant::with(['contacts.role'])->find($tenantId);

        if (! $tenant) {
            $request->session()->forget('tenant_id');

            return redirect()->route('tenants.manage')
                ->with('status', 'The selected tenant could not be found.');
        }

        $allowedTenantIds = TenantAccessManager::allowedTenantIds($request);
        if ($allowedTenantIds->isNotEmpty() && ! $allowedTenantIds->contains($tenant->id)) {
            abort(403);
        }

        $isPlayerSession = $request->session()->has('active_player_id');
        $activePlayerId = (int) $request->session()->get('active_player_id', 0);
        $activePlayerId = $activePlayerId > 0 ? $activePlayerId : null;

        $categories = config('tenant.categories', []);
        $pages = [];

        foreach ($categories as $category) {
            foreach ($category['pages'] ?? [] as $pageKey => $pageTitle) {
                $pages[$pageKey] = $pageTitle;
            }
        }

        abort_unless(array_key_exists($page, $pages), 404);

        $user = $request->user();
        $tenantPageUser = $user instanceof User ? $user : null;
        $accessiblePages = TenantPageAuthorization::accessiblePages($tenantPageUser);

        if (! array_key_exists($page, $accessiblePages)) {
            $fallbackPage = array_key_first($accessiblePages);

            if ($fallbackPage) {
                return redirect()->route('tenants.pages.show', ['page' => $fallbackPage]);
            }

            abort(403);
        }

        if ($user && method_exists($user, 'isTenantContact') && $user->isTenantContact()) {
            $contactTenantId = $user->tenantContact ? $user->tenantContact->tenant_id : null;
            if ($contactTenantId !== $tenant->id) {
                abort(403);
            }
        }

        $activityLogs = null;
        $permissionGroups = collect();
        $permissionDefinitions = collect();
        $tenantPlayers = collect();
        $permissionsOverview = null;
        $supportTickets = null;
        $selectedTicket = null;
        $supportTicketFilters = [];
        $supportTicketHighlightId = $request->query('highlight_ticket');
        $supportAgents = collect();
        $supportContacts = collect();
        $supportPlayers = collect();
        $supportTicketStats = [];
        $supportsUser = $user instanceof User ? $user : null;
        $canManageSupport = $supportsUser ? $supportsUser->hasPermission('manage_support_tickets') : false;

        $hasSupportPermission = function (string $permission) use ($supportsUser, $canManageSupport): bool {
            if ($canManageSupport) {
                return true;
            }

            return $supportsUser ? $supportsUser->hasPermission($permission) : false;
        };

        $canCollaborate = $hasSupportPermission('support_tickets_collaborate');
        $canComment = $canCollaborate || $hasSupportPermission('support_tickets_comment');
        $canCreate = $canCollaborate || $hasSupportPermission('support_tickets_create');
        $canAttach = $canCollaborate || $hasSupportPermission('support_tickets_attach');

        $supportTicketPermissions = [
            'can_manage' => $canManageSupport,
            'can_collaborate' => $canCollaborate,
            'can_comment' => $canComment,
            'can_create' => $canCreate,
            'can_attach' => $canAttach,
            'is_player' => $isPlayerSession,
        ];

        if ($page === 'activity_logs') {
            $activityLogs = TenantActivityLog::with(['user'])
                ->where('tenant_id', $tenant->id)
                ->latest()
                ->paginate(25)
                ->appends($request->query());
        }

        switch ($page) {
            case 'permissions_overview':
                $permissionsOverview = [
                    'group_count' => $tenant->permissionGroups()->count(),
                    'permission_count' => $tenant->permissionDefinitions()->count(),
                    'player_count' => $tenant->players()->count(),
                ];
                break;

            case 'support_tickets':
                $supportTicketFilters = [
                    'status' => $request->query('status'),
                    'priority' => $request->query('priority'),
                    'search' => trim((string) $request->query('search', '')),
                ];

                $supportTicketQuery = TenantSupportTicket::query()
                    ->with([
                        'assignees.assignee',
                    ])
                    ->withCount(['notes', 'players'])
                    ->forTenant($tenant)
                    ->orderByDesc('opened_at');

                if ($isPlayerSession && $activePlayerId) {
                    $supportTicketQuery->whereHas('players', function ($query) use ($activePlayerId) {
                        $query->where('tenant_player_id', $activePlayerId);
                    });
                }

                if (! empty($supportTicketFilters['status'])) {
                    $supportTicketQuery->where('status', $supportTicketFilters['status']);
                }

                if (! empty($supportTicketFilters['priority'])) {
                    $supportTicketQuery->where('priority', $supportTicketFilters['priority']);
                }

                if ($supportTicketFilters['search'] !== '') {
                    $searchTerm = '%'.$supportTicketFilters['search'].'%';
                    $supportTicketQuery->where(function ($query) use ($searchTerm) {
                        $query->where('subject', 'like', $searchTerm)
                            ->orWhere('description', 'like', $searchTerm)
                            ->orWhere('external_reference', 'like', $searchTerm);
                    });
                }

                $supportTickets = $supportTicketQuery
                    ->paginate(15)
                    ->appends($request->query());

                if ($supportTicketHighlightId) {
                    $selectedTicket = $supportTickets->firstWhere('id', (int) $supportTicketHighlightId);

                    if (! $selectedTicket) {
                        $selectedTicket = TenantSupportTicket::query()
                            ->with([
                                'assignees.assignee',
                                'players',
                                'notes.attachments',
                                'notes.author',
                            ])
                            ->forTenant($tenant)
                            ->find($supportTicketHighlightId);
                    }
                }

                if (! $selectedTicket && $supportTickets->count() > 0) {
                    $selectedTicket = $supportTickets->first();
                }

                if ($selectedTicket) {
                    $selectedTicket->loadMissing([
                        'assignees.assignee',
                        'players',
                        'notes.attachments',
                        'notes.author',
                    ]);
                }

                $supportAgents = User::query()
                    ->whereHas('groups', function ($query) {
                        $query->where('slug', 'administrators');
                    })
                    ->orderBy('name')
                    ->get();

                $supportContacts = $tenant->contacts()->with('role')->get();
                $supportPlayers = $tenant->players()
                    ->when($isPlayerSession && $activePlayerId, fn ($query) => $query->where('id', $activePlayerId))
                    ->get();

                if ($isPlayerSession && $selectedTicket && $activePlayerId) {
                    $selectedTicket->loadMissing('players');
                    if (! $selectedTicket->players->contains('id', $activePlayerId)) {
                        $selectedTicket = null;
                    }
                }
                break;

            case 'support_performance':
                $supportTicketStats = $this->buildSupportPerformanceStats($tenant);
                break;

            case 'permissions_groups':
                $permissionGroups = $tenant->permissionGroups()
                    ->with(['parents:id,name'])
                    ->withCount(['players', 'permissions'])
                    ->orderBy('name')
                    ->get();
                break;

            case 'permissions_group_permissions':
                $permissionGroups = $tenant->permissionGroups()
                    ->withCount(['permissions', 'players'])
                    ->orderBy('name')
                    ->get();
                $permissionDefinitions = $tenant->permissionDefinitions()
                    ->withCount('groups')
                    ->orderBy('name')
                    ->get();
                break;

            case 'permissions_users':
                $tenantPlayers = $tenant->players()
                    ->with('groups')
                    ->orderBy('display_name')
                    ->get();
                $permissionGroups = $tenant->permissionGroups()->orderBy('name')->get();
                break;
        }

        return view('tenants.pages.show', [
            'tenant' => $tenant,
            'pageKey' => $page,
            'pageTitle' => $pages[$page],
            'contacts' => $tenant->contacts,
            'activityLogs' => $activityLogs,
            'permissionGroups' => $permissionGroups,
            'permissionDefinitions' => $permissionDefinitions,
            'tenantPlayers' => $tenantPlayers,
            'permissionsOverview' => $permissionsOverview,
            'supportTickets' => $supportTickets,
            'supportTicketFilters' => $supportTicketFilters,
            'supportTicketHighlightId' => $supportTicketHighlightId,
            'selectedTicket' => $selectedTicket,
            'supportAgents' => $supportAgents,
            'supportContacts' => $supportContacts,
            'supportPlayers' => $supportPlayers,
            'supportTicketPermissions' => $supportTicketPermissions,
            'supportTicketStats' => $supportTicketStats,
            'isPlayerSession' => $isPlayerSession,
        ]);
    }

    /**
     * Build aggregated statistics for support ticket performance.
     */
    protected function buildSupportPerformanceStats(Tenant $tenant): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $openCount = TenantSupportTicket::forTenant($tenant)->where('status', TenantSupportTicket::STATUS_OPEN)->count();
        $inProgressCount = TenantSupportTicket::forTenant($tenant)->where('status', TenantSupportTicket::STATUS_IN_PROGRESS)->count();
        $resolvedCount = TenantSupportTicket::forTenant($tenant)->where('status', TenantSupportTicket::STATUS_RESOLVED)->count();
        $closedCount = TenantSupportTicket::forTenant($tenant)->where('status', TenantSupportTicket::STATUS_CLOSED)->count();

        $createdThisMonth = TenantSupportTicket::forTenant($tenant)
            ->whereBetween('opened_at', [$startOfMonth, $endOfMonth])
            ->count();

        $resolvedThisMonth = TenantSupportTicket::forTenant($tenant)
            ->whereBetween('resolved_at', [$startOfMonth, $endOfMonth])
            ->whereNotNull('resolved_at')
            ->count();

        $resolvedTickets = TenantSupportTicket::forTenant($tenant)
            ->whereNotNull('opened_at')
            ->whereNotNull('resolved_at')
            ->get(['opened_at', 'resolved_at']);

        $averageResolutionSeconds = $resolvedTickets
            ->map(function (TenantSupportTicket $ticket) {
                if (! $ticket->opened_at || ! $ticket->resolved_at) {
                    return null;
                }

                return $ticket->opened_at->diffInSeconds($ticket->resolved_at);
            })
            ->filter()
            ->avg();

        $averageResolutionHours = $averageResolutionSeconds
            ? round($averageResolutionSeconds / 3600, 2)
            : null;

        $trendSeries = TenantSupportTicket::forTenant($tenant)
            ->selectRaw('DATE_FORMAT(opened_at, "%Y-%m") as month_key, COUNT(*) as total')
            ->whereNotNull('opened_at')
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->limit(12)
            ->get()
            ->map(function ($row) {
                return [
                    'month' => $row->month_key,
                    'total' => (int) $row->total,
                ];
            })
            ->all();

        $assigneeStats = TenantSupportTicketAssignee::query()
            ->with('assignee')
            ->whereHas('ticket', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id);
            })
            ->select([
                'assignee_type',
                'assignee_id',
                DB::raw('COUNT(*) as total_assignments'),
            ])
            ->groupBy('assignee_type', 'assignee_id')
            ->orderByDesc('total_assignments')
            ->limit(5)
            ->get()
            ->map(function (TenantSupportTicketAssignee $assignee) {
                $label = $this->formatAssigneeLabel($assignee->assignee);

                return [
                    'label' => $label,
                    'tickets' => (int) $assignee->total_assignments,
                ];
            })
            ->filter(fn ($stat) => ! empty($stat['label']))
            ->values()
            ->all();

        $totalTickets = $openCount + $inProgressCount + $resolvedCount + $closedCount;
        $resolvedOverall = $resolvedCount + $closedCount;

        return [
            'totals' => [
                'open' => $openCount,
                'in_progress' => $inProgressCount,
                'resolved' => $resolvedCount,
                'closed' => $closedCount,
            ],
            'this_month' => [
                'created' => $createdThisMonth,
                'resolved' => $resolvedThisMonth,
            ],
            'average_resolution_hours' => $averageResolutionHours,
            'resolution_rate' => $totalTickets > 0 ? round(($resolvedOverall / $totalTickets) * 100, 1) : null,
            'trend' => $trendSeries,
            'top_assignees' => $assigneeStats,
        ];
    }

    /**
     * Derive a display label for an assignee record.
     */
    protected function formatAssigneeLabel($assignee): ?string
    {
        if ($assignee instanceof User) {
            return $assignee->name;
        }

        if ($assignee instanceof TenantContact) {
            return $assignee->name;
        }

        return null;
    }
}
