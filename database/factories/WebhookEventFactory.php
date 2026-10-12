<?php

namespace Database\Factories;

use App\Models\Source;
use App\Models\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookEvent>
 */
class WebhookEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'source_id' => Source::factory(),
            'organization_id' => fn (array $attributes) => Source::withoutGlobalScopes()->find($attributes['source_id'])->organization_id,
            'method' => 'POST',
            'headers' => ['content-type' => ['application/json']],
            'body' => json_encode(['evenement' => 'commande.creee', 'id' => fake()->numberBetween(1, 9999)]),
            'content_type' => 'application/json',
            'ip' => '203.0.113.10',
            'signature_status' => 'missing',
        ];
    }
}
