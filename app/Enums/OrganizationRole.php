<?php

namespace App\Enums;

enum OrganizationRole: string
{
    case Owner = 'owner';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Propriétaire',
            self::Member => 'Membre',
        };
    }
}
