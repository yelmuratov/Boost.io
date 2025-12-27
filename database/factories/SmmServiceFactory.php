<?php

namespace Database\Factories;

use App\Models\SmmService;
use App\Models\SmmProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SmmService>
 */
class SmmServiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SmmService::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cost = $this->faker->randomFloat(4, 0.001, 10);
        return [
            'provider_id' => SmmProvider::inRandomOrder()->first()->id ?? SmmProvider::factory(),
            'service_id' => $this->faker->unique()->numberBetween(1000, 99999),
            'name' => $this->faker->bs() . ' ' . $this->faker->word(),
            'type' => $this->faker->randomElement(['default', 'custom_comments', 'subscriptions', 'poll']),
            'category' => $this->faker->randomElement(['Instagram', 'Facebook', 'TikTok', 'YouTube', 'Twitter']),
            'cost' => $cost,
            'rate' => $cost * 1.5, // 50% markup
            'min' => $this->faker->numberBetween(10, 100),
            'max' => $this->faker->numberBetween(1000, 1000000),
            'is_active' => $this->faker->boolean(90),
            'description' => $this->faker->catchPhrase(),
            'metadata' => [],
        ];
    }
}
