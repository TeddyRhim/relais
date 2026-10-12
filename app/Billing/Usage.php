<?php

namespace App\Billing;

use App\Models\Organization;
use App\Models\WebhookEvent;
use App\Tenancy\CurrentOrganization;

/**
 * Consommation du quota mensuel d'une organisation (événements reçus pendant le mois civil en cours).
 * Le dépassement peut excéder de quelques unités sous forte concurrence : le quota est une limite
 * commerciale, pas une garantie stricte.
 */
class Usage
{
    public function __construct(private CurrentOrganization $current) {}

    public function used(Organization $organization): int
    {
        return $this->current->run($organization, fn () => WebhookEvent::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count());
    }

    public function limit(Organization $organization): int
    {
        return $organization->plan->monthlyEventLimit();
    }

    public function remaining(Organization $organization): int
    {
        return max(0, $this->limit($organization) - $this->used($organization));
    }

    public function isExceeded(Organization $organization): bool
    {
        return $this->remaining($organization) === 0;
    }

    public function percent(Organization $organization): int
    {
        $limit = $this->limit($organization);

        return $limit === 0 ? 100 : (int) min(100, floor($this->used($organization) * 100 / $limit));
    }
}
