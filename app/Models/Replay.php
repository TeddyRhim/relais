<?php

namespace App\Models;

use App\Tenancy\BelongsToOrganization;
use Database\Factories\ReplayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Replay extends Model
{
    /** @use HasFactory<ReplayFactory> */
    use BelongsToOrganization, HasFactory;

    public const PENDING = 'pending';

    public const RETRYING = 'retrying';

    public const SUCCESS = 'success';

    public const FAILED = 'failed';

    protected $fillable = ['webhook_event_id', 'target_url', 'status', 'attempts', 'response_status', 'response_excerpt', 'error', 'last_attempt_at'];

    protected function casts(): array
    {
        return ['last_attempt_at' => 'datetime'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(WebhookEvent::class, 'webhook_event_id');
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::SUCCESS, self::FAILED], true);
    }
}
