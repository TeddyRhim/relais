<?php

namespace App\Billing;

use App\Models\Organization;

/**
 * Orchestration : appelle le fournisseur puis applique le plan si celui-ci ne passe pas par un paiement hébergé.
 */
class BillingService
{
    public function __construct(private BillingProvider $provider) {}

    /**
     * @return string|null adresse de paiement à ouvrir, ou null si le changement est déjà appliqué
     */
    public function subscribe(Organization $organization, Plan $plan): ?string
    {
        if ($plan === Plan::Free) {
            return $this->cancel($organization);
        }

        $redirect = $this->provider->subscribe($organization, $plan);

        if ($redirect === null) {
            $this->applyPlan($organization, $plan);
        }

        return $redirect;
    }

    public function cancel(Organization $organization): ?string
    {
        $redirect = $this->provider->cancel($organization);

        if ($redirect === null) {
            $this->applyPlan($organization, Plan::Free);
        }

        return $redirect;
    }

    public function applyPlan(Organization $organization, Plan $plan): void
    {
        $organization->forceFill(['plan' => $plan])->save();
    }
}
