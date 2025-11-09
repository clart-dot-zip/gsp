<?php

namespace App\Models;

use App\Models\TenantActivityLog;
use App\Models\TenantApiKey;
use App\Models\TenantContact;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string|null $slug
 * @property-read \Illuminate\Support\Collection<int, \App\Models\TenantContact> $contacts
 * @property-read \Illuminate\Support\Collection<int, \App\Models\TenantGroup> $permissionGroups
 * @property-read \Illuminate\Support\Collection<int, \App\Models\TenantPermission> $permissionDefinitions
 * @property-read \Illuminate\Support\Collection<int, \App\Models\TenantPlayer> $players
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Tenant query()
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Tenant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'contact_email',
        'website_url',
        'description',
    ];

    /**
     * Resolve the tenant display name.
     */
    public function displayName(): string
    {
        return $this->name;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contacts()
    {
        return $this->hasMany(TenantContact::class)->orderBy('name');
    }

    /**
     * @return HasMany<TenantGroup>
     */
    public function permissionGroups(): HasMany
    {
        return $this->hasMany(TenantGroup::class)->orderBy('name');
    }

    /**
     * Alias for permissionGroups to maintain backwards compatibility.
     *
     * @return HasMany<TenantGroup>
     */
    public function groups(): HasMany
    {
        return $this->permissionGroups();
    }

    /**
     * @return HasMany<TenantPermission>
     */
    public function permissionDefinitions(): HasMany
    {
        return $this->hasMany(TenantPermission::class)->orderBy('name');
    }

    /**
     * Alias for permissionDefinitions to support route-model binding.
     *
     * @return HasMany<TenantPermission>
     */
    public function permissions(): HasMany
    {
        return $this->permissionDefinitions();
    }

    /**
     * @return HasMany<TenantPlayer>
     */
    public function players(): HasMany
    {
        return $this->hasMany(TenantPlayer::class)->orderBy('display_name');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activityLogs()
    {
        return $this->hasMany(TenantActivityLog::class)->latest();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function apiKeys()
    {
        return $this->hasMany(TenantApiKey::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function dataCollectorKey()
    {
        return $this->hasOne(TenantApiKey::class)->where('key_type', TenantApiKey::TYPE_DATA_COLLECTOR);
    }

    /**
     * Build a unique slug from a tenant name.
     */
    public static function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);

        if ($baseSlug === '') {
            $baseSlug = Str::slug(Str::random(8));
        }
        $slug = $baseSlug;
        $suffix = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
