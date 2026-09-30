<?php

namespace Database\Factories\Inventory;

use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvSnapshot>
 */
class InvSnapshotFactory extends Factory
{
    protected $model = InvSnapshot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $payload = json_encode(['content' => ['hardware' => ['name' => 'PC-0001']]]);

        return [
            'inv_agent_id' => InvAgent::factory(),
            'payload' => $payload,
            'content_hash' => hash('sha256', $payload),
            'protocol' => 'json',
            'received_at' => now(),
        ];
    }
}
