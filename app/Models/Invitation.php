<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use App\Tenancy\BelongsToOrganization;
use Database\Factories\InvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    /** @use HasFactory<InvitationFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = ['email', 'role', 'token_hash', 'expires_at', 'accepted_at'];

    protected function casts(): array
    {
        return [
            'role' => OrganizationRole::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Retrouve une invitation en attente par son jeton. L'appelant n'a pas de contexte
     * d'organisation (invité non connecté) : c'est le seul endroit où le scope est levé,
     * et la recherche se fait sur un secret.
     */
    public static function findPendingByToken(string $token): ?self
    {
        return static::withoutGlobalScopes()
            ->where('token_hash', static::hashToken($token))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();
    }
}
