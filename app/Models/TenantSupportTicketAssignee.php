<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $tenant_support_ticket_id
 * @property string $assignee_type
 * @property int $assignee_id
 * @property Carbon|null $assigned_at
 */
class TenantSupportTicketAssignee extends Model
{
    use HasFactory;

    /** @var string[] */
    protected $fillable = [
        'tenant_id',
        'tenant_support_ticket_id',
        'assignee_type',
        'assignee_id',
        'assigned_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<TenantSupportTicket, self>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(TenantSupportTicket::class, 'tenant_support_ticket_id');
    }

    /**
     * @return MorphTo<Model, self>
     */
    public function assignee(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Determine if the assignee is a user record.
     */
    public function isUser(): bool
    {
        return $this->assignee_type === User::class;
    }
}
