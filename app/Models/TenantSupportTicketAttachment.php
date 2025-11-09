<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $tenant_support_ticket_note_id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string|null $mime_type
 * @property int $size
 */
class TenantSupportTicketAttachment extends Model
{
    use HasFactory;

    /** @var string[] */
    protected $fillable = [
        'tenant_id',
        'tenant_support_ticket_note_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'size' => 'int',
    ];

    /**
     * @return BelongsTo<TenantSupportTicketNote, self>
     */
    public function note(): BelongsTo
    {
        return $this->belongsTo(TenantSupportTicketNote::class, 'tenant_support_ticket_note_id');
    }

    /**
     * @return BelongsTo<Tenant, self>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Generate a temporary URL for the stored attachment when possible.
     */
    public function temporaryUrl(int $seconds = 900): ?string
    {
        $disk = $this->disk ?: Config::get('filesystems.default', 'public');
        $storage = Storage::disk($disk);

        if (method_exists($storage, 'temporaryUrl')) {
            return $storage->temporaryUrl($this->path, Carbon::now()->addSeconds($seconds));
        }

        if (method_exists($storage, 'url')) {
            return $storage->url($this->path);
        }

        return null;
    }
}
