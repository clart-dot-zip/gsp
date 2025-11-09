<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $display_name
 * @property string|null $steam_id
 * @property string|null $avatar_url
 * @property \Illuminate\Support\Carbon|null $last_synced_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|TenantPlayer query()
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class TenantPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'display_name',
        'steam_id',
        'avatar_url',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Tenant, self>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsToMany<TenantGroup>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(TenantGroup::class, 'tenant_player_group')
            ->withTimestamps();
    }
}
