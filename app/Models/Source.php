<?php

namespace App\Models;

use App\Tenancy\BelongsToOrganization;
use Database\Factories\SourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Source extends Model
{
    /** @use HasFactory<SourceFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = ['name', 'signature_header', 'require_signature', 'target_url'];

    protected $hidden = ['signing_secret'];

    protected function casts(): array
    {
        return [
            'signing_secret' => 'encrypted',
            'require_signature' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Source $source) {
            $source->token ??= Str::random(40);
            $source->signing_secret ??= Str::random(48);
            $source->signature_header ??= config('relais.default_signature_header');
        });
    }

    public function events(): HasMany
    {
        return $this->hasMany(WebhookEvent::class);
    }

    public function receptionUrl(): string
    {
        return url('/h/'.$this->token);
    }

    /**
     * Retrouve une source par son jeton d'URL. L'émetteur du webhook n'a pas de session :
     * c'est avec cette recherche (sur un secret) que le contexte d'organisation est établi.
     */
    public static function findByToken(string $token): ?self
    {
        return static::withoutGlobalScopes()->where('token', $token)->first();
    }
}
