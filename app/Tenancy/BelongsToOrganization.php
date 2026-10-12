<?php

namespace App\Tenancy;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * À utiliser sur tout modèle de données client : scope global et organization_id automatique.
 */
trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope);

        static::creating(function ($model) {
            if ($model->organization_id !== null) {
                return;
            }

            $id = app(CurrentOrganization::class)->id();

            if ($id === null) {
                throw new LogicException('Aucune organisation courante pour créer '.$model::class.'.');
            }

            $model->organization_id = $id;
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
