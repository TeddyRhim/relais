<?php

namespace Database\Factories;

use App\Models\Replay;
use App\Models\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Replay>
 */
class ReplayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'webhook_event_id' => WebhookEvent::factory(),
            'organization_id' => fn (array $attributes) => WebhookEvent::withoutGlobalScopes()->find($attributes['webhook_event_id'])->organization_id,
            'target_url' => 'https://93.184.216.34/hook',
        ];
    }
}
