<?php

namespace App\Billing;

use App\Models\Organization;

/**
 * Faux fournisseur : aucun appel réseau, le plan est appliqué tout de suite.
 */
class FakeBillingProvider implements BillingProvider
{
    public function subscribe(Organization $organization, Plan $plan): ?string
    {
        $organization->forceFill(['billing_reference' => $organization->billing_reference ?? 'fake_sub_'.$organization->id])->save();

        return null;
    }

    public function cancel(Organization $organization): ?string
    {
        return null;
    }
}
