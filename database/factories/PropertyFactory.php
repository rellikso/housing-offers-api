<?php

namespace Database\Factories;

use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(),
            'name' => fake()->company(),
            'city' => fake()->city(),
        ];
    }
}