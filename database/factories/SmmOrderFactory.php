<?php

namespace Database\Factories;

use App\Models\SmmOrder;
use App\Models\SmmProvider;
use App\Models\SmmService;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SmmOrder>
 */
class SmmOrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SmmOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->first()->id ?? User::factory(),
            'provider_id' => SmmProvider::inRandomOrder()->first()->id ?? SmmProvider::factory(),
            'service_id' => SmmService::inRandomOrder()->first()->id ?? SmmService::factory(),
            'order_id' => $this->faker->unique()->numerify('###'),
            'link' => $this->faker->url(),
            'quantity' => $this->faker->numberBetween(100, 10000),
            'charge' => $this->faker->randomFloat(2, 0.1, 100),
            'cost' => $this->faker->randomFloat(2, 0.05, 50),
            'start_count' => $this->faker->numberBetween(0, 100),
            'remains' => $this->faker->numberBetween(0, 50),
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'canceled', 'partial']),
            'order_data' => ['note' => $this->faker->sentence()],
            'response_data' => ['api_id' => $this->faker->numerify('#####')],
        ];
    }
}
