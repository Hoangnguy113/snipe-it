<?php

namespace Database\Factories\Inventory;

use App\Models\Inventory\InvAgent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvAgent>
 */
class InvAgentFactory extends Factory
{
    protected $model = InvAgent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hostname = 'PC-'.$this->faker->unique()->numerify('####');

        return [
            'deviceid' => $hostname.'-'.$this->faker->dateTime()->format('Y-m-d-H-i-s'),
            'agent_uuid' => $this->faker->unique()->uuid(),
            'hostname' => $hostname,
            'tag' => 'PHONG-KE-TOAN',
            'agent_version' => '1.20',
            'state' => 'active',
        ];
    }
}
