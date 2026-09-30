<?php

namespace Database\Factories\Inventory;

use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvUnmatched;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvUnmatched>
 */
class InvUnmatchedFactory extends Factory
{
    protected $model = InvUnmatched::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inv_agent_id' => InvAgent::factory(),
            'hostname' => 'PC-'.$this->faker->numerify('####'),
            'serial' => strtoupper($this->faker->bothify('??########')),
            'reason' => 'no_match',
        ];
    }
}
