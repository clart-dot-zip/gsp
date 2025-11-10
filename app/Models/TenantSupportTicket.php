<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $subject
 * @property string|null $description
 * @property string $status
 * @property string $priority
 * @property string|null $external_reference
 * @property int|null $created_by_user_id
 * @property int|null $created_by_contact_id
 * @property int|null $created_by_player_id
 * @property Carbon|null $opened_at
 * @property Carbon|null $resolved_at
 * @property Carbon|null $closed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|self forTenant(int|Tenant $tenant)
 * @method static Builder|self query()
 */
class TenantSupportTicket extends Model
{
    use HasFactory;

    /** @var string[] */
    protected $fillable = [
        'tenant_id',
        'subject',
        'description',
        'status',
        'priority',
        'external_reference',
        'created_by_user_id',
        'created_by_contact_id',
        'created_by_player_id',
        'opened_at',
        'resolved_at',
        'closed_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'opened_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_CRITICAL = 'critical';

    /**
     * @return BelongsTo<Tenant, self>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<User, self>
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return BelongsTo<TenantContact, self>
     */
    public function createdByContact(): BelongsTo
    {
        return $this->belongsTo(TenantContact::class, 'created_by_contact_id');
    }

    /**
     * @return BelongsTo<TenantPlayer, self>
     */
    public function createdByPlayer(): BelongsTo
    {
        return $this->belongsTo(TenantPlayer::class, 'created_by_player_id');
    }

    /**
     * @return HasMany<TenantSupportTicketNote>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(TenantSupportTicketNote::class)
            ->orderBy('created_at');
    }

    /**
     * @return HasOne<TenantSupportTicketNote>
     */
    public function latestNote(): HasOne
    {
        return $this->hasOne(TenantSupportTicketNote::class)
            ->latestOfMany();
    }

    /**
     * @return HasMany<TenantSupportTicketAssignee>
     */
    public function assignees(): HasMany
    {
        return $this->hasMany(TenantSupportTicketAssignee::class);
    }

    /**
     * @return BelongsToMany<TenantPlayer>
     */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(TenantPlayer::class, 'tenant_support_ticket_players')
            ->withTimestamps();
    }

    /**
     * Scope tickets to a tenant.
     */
    public function scopeForTenant(Builder $query, $tenant): Builder
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : (int) $tenant;

        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Determine if the ticket has any active assignees.
     */
    public function hasAssignees(): bool
    {
        return $this->relationLoaded('assignees')
            ? $this->assignees->isNotEmpty()
            : $this->assignees()->exists();
    }

    /**
     * Sync the provided assignees with the ticket.
     *
     * @param Collection<int, array{type: class-string, id: int}> $assignees
     */
    public function syncAssignees(Collection $assignees): void
    {
        $idsToKeep = [];

        foreach ($assignees as $assignee) {
            $record = $this->assignees()->updateOrCreate(
                [
                    'tenant_support_ticket_id' => $this->id,
                    'assignee_type' => $assignee['type'],
                    'assignee_id' => $assignee['id'],
                ],
                [
                    'tenant_id' => $this->tenant_id,
                    'assigned_at' => Carbon::now(),
                ]
            );

            $idsToKeep[] = $record->id;
        }

        if (empty($idsToKeep)) {
            $this->assignees()->delete();
        } else {
            $this->assignees()
                ->whereNotIn('id', $idsToKeep)
                ->delete();
        }
    }

    /**
     * Check whether the given user can claim the ticket.
     */
    public function canBeClaimedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasPermission('manage_support_tickets')) {
            return true;
        }

        return $user->isTenantContact()
            && $user->tenantContact
            && $user->tenantContact->tenant_id === $this->tenant_id;
    }

    /**
     * Determine whether the user is currently assigned to the ticket.
     */
    public function isAssignedTo(User $user): bool
    {
        return $this->assignees()
            ->where('assignee_type', User::class)
            ->where('assignee_id', $user->id)
            ->exists();
    }

    /**
     * Resolve the status display label.
     */
    public function statusLabel(): string
    {
        switch ($this->status) {
            case self::STATUS_IN_PROGRESS:
                return 'In Progress';
            case self::STATUS_RESOLVED:
                return 'Resolved';
            case self::STATUS_CLOSED:
                return 'Closed';
            default:
                return 'Open';
        }
    }

    /**
     * Resolve the priority display label.
     */
    public function priorityLabel(): string
    {
        switch ($this->priority) {
            case self::PRIORITY_LOW:
                return 'Low';
            case self::PRIORITY_HIGH:
                return 'High';
            case self::PRIORITY_CRITICAL:
                return 'Critical';
            default:
                return 'Normal';
        }
    }
}
