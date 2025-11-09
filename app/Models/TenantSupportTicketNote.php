<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $tenant_support_ticket_id
 * @property string|null $body
 * @property bool $is_resolution
 * @property int|null $timer_seconds
 * @property Carbon|null $timer_started_at
 * @property Carbon|null $timer_stopped_at
 * @property array|null $meta
 */
class TenantSupportTicketNote extends Model
{
    use HasFactory;

    /** @var string[] */
    protected $fillable = [
        'tenant_id',
        'tenant_support_ticket_id',
        'author_type',
        'author_id',
        'body',
        'is_resolution',
        'timer_seconds',
        'timer_started_at',
        'timer_stopped_at',
        'meta',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_resolution' => 'bool',
        'timer_seconds' => 'int',
        'timer_started_at' => 'datetime',
        'timer_stopped_at' => 'datetime',
        'meta' => 'array',
    ];

    /**
     * @return BelongsTo<TenantSupportTicket, self>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(TenantSupportTicket::class, 'tenant_support_ticket_id');
    }

    /**
     * @return BelongsTo<Tenant, self>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return MorphTo<Model, self>
     */
    public function author(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<TenantSupportTicketAttachment>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TenantSupportTicketAttachment::class, 'tenant_support_ticket_note_id');
    }

    /**
     * Determine whether the note includes an elapsed timer value.
     */
    public function hasTimer(): bool
    {
        return ! is_null($this->timer_seconds) && $this->timer_seconds > 0;
    }
}
