<?php

namespace Database\Factories;

use App\Models\Import;
use App\Models\Offer;
use App\Models\Property;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    protected $model = Offer::class;

    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'property_id' => Property::factory(),
            'import_id' => Import::factory(),
            'external_id' => fake()->unique()->uuid(),

            'check_in' => now()->addDays(30)->toDateString(),
            'check_out' => now()->addDays(35)->toDateString(),

            'max_guests' => 2,

            'price' => fake()->randomFloat(2, 50, 500),
            'currency' => 'USD',

            'available_units' => 3,

            'expires_at' => now()->addDay(),
        ];
    }

    public function forImport(Import $import): static
    {
        return $this->state([
            'supplier_id' => $import->supplier_id,
            'import_id' => $import->id,
        ]);
    }
}