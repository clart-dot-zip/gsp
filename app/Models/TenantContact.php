<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int|null $contact_role_id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $preferred_method
 * @property string|null $notes
 * @property string|null $steam_id
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\Tenant|null $tenant
 * @property-read \App\Models\ContactRole|null $role
 *
 * @method static \Illuminate\Database\Eloquent\Builder|TenantContact query()
 * @method static \Illuminate\Database\Eloquent\Builder|TenantContact firstWhere(string $column, $operator = null, $value = null, $boolean = 'and')
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class TenantContact extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'contact_role_id',
        'steam_id',
        'name',
        'email',
        'phone',
        'preferred_method',
        'notes',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(ContactRole::class, 'contact_role_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class);
    }
}
