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
 * @method static \Illuminate\Database\Eloquent\Builder|TenantPermission query()
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class TenantPermission extends Model
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
     * @return BelongsToMany<TenantGroup>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(TenantGroup::class, 'tenant_group_permission')
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
