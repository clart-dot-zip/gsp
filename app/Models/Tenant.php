<?php

namespace App\Models;

use App\Models\TenantActivityLog;
use App\Models\TenantContact;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string|null $slug
 * @property-read \Illuminate\Support\Collection<int, \App\Models\TenantContact> $contacts
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activityLogs()
    {
        return $this->hasMany(TenantActivityLog::class)->latest();
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
