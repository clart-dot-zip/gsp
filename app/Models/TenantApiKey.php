<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantApiKey extends Model
{
    use HasFactory;

    public const TYPE_DATA_COLLECTOR = 'data_collector';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'key_type',
        'key_hash',
        'last_four',
        'last_used_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'key_hash',
    ];

    /**
     * @return BelongsTo<Tenant, self>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
