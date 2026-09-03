<?php

namespace Database\Factories;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Import>
 */
class ImportFactory extends Factory
{
    protected $model = Import::class;

    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'external_import_id' => fake()->unique()->uuid(),
            'sent_at' => now(),

            'status' => ImportStatus::Completed,

            'total_offers' => 0,
            'processed_offers' => 0,

            'error' => null,
            'completed_at' => now(),
        ];
    }
}