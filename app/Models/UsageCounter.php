<?php

namespace App\Models;

use App\Tenancy\BelongsToOrganization;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UsageCounter extends Model
{
    use BelongsToOrganization;

    public $timestamps = false;

    /**
     * Ajoute un événement au mois donné, de façon atomique (une seule requête, sans lecture préalable).
     */
    public static function record(int $organizationId, CarbonInterface $at): void
    {
        DB::table('usage_counters')->upsert(
            [['organization_id' => $organizationId, 'period' => $at->format('Y-m'), 'events' => 1]],
            ['organization_id', 'period'],
            ['events' => DB::raw('usage_counters.events + 1')],
        );
    }
}
