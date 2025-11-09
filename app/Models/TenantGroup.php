<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $external_reference
 *
 * @method static \Illuminate\Database\Eloquent\Builder|TenantGroup query()
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class TenantGroup extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'external_reference',
    ];

    /**
     * @return BelongsTo<Tenant, self>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsToMany<TenantPermission>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(TenantPermission::class, 'tenant_group_permission')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<TenantGroup>
     */
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'tenant_group_inheritances',
            'child_group_id',
            'parent_group_id'
        )->withTimestamps();
    }

    /**
     * @return BelongsToMany<TenantGroup>
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'tenant_group_inheritances',
            'parent_group_id',
            'child_group_id'
        )->withTimestamps();
    }

    /**
     * @return BelongsToMany<TenantPlayer>
     */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(TenantPlayer::class, 'tenant_player_group')
            ->withTimestamps();
    }

    public static function generateUniqueSlug(string $name, int $tenantId): string
    {
        $baseSlug = Str::slug($name);

        if ($baseSlug === '') {
            $baseSlug = Str::slug(Str::random(8));
        }

        $slug = $baseSlug;
        $suffix = 1;

        while (static::where('tenant_id', $tenantId)->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
