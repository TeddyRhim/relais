<?php

namespace App\Tenancy;

use App\Models\Organization;

/**
 * Contexte d'organisation courant : source unique de vérité pour le scope global.
 * Sans contexte, les modèles scopés ne renvoient aucune ligne (échec fermé).
 */
class CurrentOrganization
{
    private ?int $id = null;

    public function id(): ?int
    {
        return $this->id;
    }

    public function set(Organization|int|null $organization): void
    {
        $this->id = $organization instanceof Organization ? $organization->getKey() : $organization;
    }

    public function forget(): void
    {
        $this->id = null;
    }

    /**
     * Exécute un bloc dans le contexte d'une organisation, puis restaure le précédent.
     *
     * @template T
     *
     * @param  callable():T  $callback
     * @return T
     */
    public function run(Organization|int $organization, callable $callback): mixed
    {
        $previous = $this->id;
        $this->set($organization);

        try {
            return $callback();
        } finally {
            $this->id = $previous;
        }
    }
}
