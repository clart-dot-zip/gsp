<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $steam_id
 * @property int|null $tenant_contact_id
 * @property-read \Illuminate\Support\Collection<int, \App\Models\Group> $groups
 * @property-read \App\Models\TenantContact|null $tenantContact
 *
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Model|User firstOrNew(array $attributes, array $values = [])
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'authentik_id',
        'avatar',
        'steam_id',
        'tenant_contact_id',
    ];

    /**
     * @return BelongsToMany<Group>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_user')->withTimestamps();
    }

    /**
     * @return BelongsTo<TenantContact, self>
     */
    public function tenantContact(): BelongsTo
    {
        return $this->belongsTo(TenantContact::class);
    }

    /**
     * Determine if the user has the given permission slug.
     */
    public function hasPermission(string $permissionSlug): bool
    {
        return $this->groups()
            ->whereHas('permissions', function ($query) use ($permissionSlug) {
                $query->where('slug', $permissionSlug);
            })
            ->exists();
    }

    /**
     * Check whether the user represents a tenant contact record.
     */
    public function isTenantContact(): bool
    {
        return (bool) $this->tenant_contact_id;
    }
}
