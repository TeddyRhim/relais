<?php

namespace App\Models;

use App\Tenancy\BelongsToOrganization;
use Database\Factories\WebhookEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookEvent extends Model
{
    /** @use HasFactory<WebhookEventFactory> */
    use BelongsToOrganization, HasFactory;

    public const SIGNATURE_VALID = 'valid';

    public const SIGNATURE_INVALID = 'invalid';

    public const SIGNATURE_MISSING = 'missing';

    protected $fillable = ['source_id', 'method', 'headers', 'body', 'content_type', 'ip', 'signature_status'];

    protected function casts(): array
    {
        return ['headers' => 'array'];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function replays(): HasMany
    {
        return $this->hasMany(Replay::class);
    }
}
