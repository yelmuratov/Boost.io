<?php

namespace Database\Factories;

use App\Models\SmmProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SmmProvider>
 */
class SmmProviderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SmmProvider::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'api_url' => $this->faker->url(),
            'api_key' => $this->faker->sha256(),
            'is_active' => $this->faker->boolean(80),
            'verification_status' => $this->faker->boolean(90),
            'priority' => $this->faker->numberBetween(1, 10),
            'markup_percentage' => $this->faker->randomFloat(2, 10, 50),
            'balance' => $this->faker->randomFloat(2, 0, 1000),
            'last_sync_at' => $this->faker->dateTimeThisMonth(),
            'metadata' => ['contact' => $this->faker->email()],
        ];
    }
}
