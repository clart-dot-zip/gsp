<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int|null $tenant_player_id
 * @property string $player_name
 * @property string|null $player_steam_id
 * @property string $reason
 * @property string|null $admin_reason
 * @property int|null $created_by_user_id
 * @property int|null $created_by_contact_id
 * @property Carbon|null $banned_at
 *
 * @method static Builder|self forTenant(int|Tenant $tenant)
 * @method static Builder|self query()
 */
class TenantBan extends Model
{
    use HasFactory;

    /** @var string[] */
    protected $fillable = [
        'tenant_id',
        'tenant_player_id',
        'player_name',
        'player_steam_id',
        'reason',
        'admin_reason',
        'created_by_user_id',
        'created_by_contact_id',
        'banned_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'banned_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Tenant, self>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<TenantPlayer, self>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(TenantPlayer::class, 'tenant_player_id');
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
     * Scope bans to a specific tenant.
     */
    public function scopeForTenant(Builder $query, $tenant): Builder
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : (int) $tenant;

        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Resolve a display label for the banning administrator.
     */
    public function banningAdminLabel(): string
    {
        if ($this->relationLoaded('createdByContact') && $this->createdByContact) {
            return $this->createdByContact->name;
        }

        if ($this->relationLoaded('createdByUser') && $this->createdByUser) {
            return $this->createdByUser->name;
        }

        if ($this->createdByContact) {
            return $this->createdByContact->name;
        }

        if ($this->createdByUser) {
            return $this->createdByUser->name;
        }

        return 'System';
    }
}
