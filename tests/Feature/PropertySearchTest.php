<?php

namespace Tests\Feature;

use App\Models\Import;
use App\Models\Offer;
use App\Models\Property;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertySearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_cheapest_matching_offer_for_each_property(): void
    {
        $supplier = Supplier::factory()->create();

        $import = Import::factory()
            ->create([
                'supplier_id' => $supplier->id,
            ]);

        $property = Property::factory()->create([
            'code' => 'hotel-001',
            'name' => 'Grand Hotel',
            'city' => 'Kyiv',
        ]);

        Offer::factory()
            ->forImport($import)
            ->create([
                'property_id' => $property->id,
                'external_id' => 'offer-001',
                'check_in' => '2026-10-01',
                'check_out' => '2026-10-05',
                'max_guests' => 2,
                'price' => 200,
                'currency' => 'USD',
                'available_units' => 3,
                'expires_at' => now()->addDay(),
            ]);

        $cheapOffer = Offer::factory()
            ->forImport($import)
            ->create([
                'property_id' => $property->id,
                'external_id' => 'offer-002',
                'check_in' => '2026-10-01',
                'check_out' => '2026-10-05',
                'max_guests' => 2,
                'price' => 150,
                'currency' => 'USD',
                'available_units' => 3,
                'expires_at' => now()->addDay(),
            ]);

        $response = $this->getJson('/api/properties?' . http_build_query([
                'check_in' => '2026-10-01',
                'check_out' => '2026-10-05',
                'guests' => 2,
                'currency' => 'USD',
            ]));

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $property->id)
            ->assertJsonPath('data.0.best_offer.id', $cheapOffer->id)
            ->assertJsonPath('data.0.best_offer.price', '150.00')
            ->assertJsonPath('data.0.best_offer.currency', 'USD');
    }

    public function test_ignores_offers_that_do_not_match_search_criteria(): void
    {
        $supplier = Supplier::factory()->create();

        $import = Import::factory()->create([
            'supplier_id' => $supplier->id,
        ]);

        $property = Property::factory()->create([
            'city' => 'Kyiv',
        ]);

        $validOffer = Offer::factory()
            ->forImport($import)
            ->create([
                'property_id' => $property->id,
                'external_id' => 'valid',
                'check_in' => '2026-10-01',
                'check_out' => '2026-10-05',
                'max_guests' => 2,
                'price' => 150,
                'currency' => 'USD',
                'available_units' => 1,
                'expires_at' => now()->addDay(),
            ]);

        Offer::factory()
            ->forImport($import)
            ->create([
                'property_id' => $property->id,
                'external_id' => 'wrong-dates',
                'check_in' => '2026-10-02',
                'check_out' => '2026-10-06',
                'price' => 50,
                'currency' => 'USD',
            ]);

        Offer::factory()
            ->forImport($import)
            ->create([
                'property_id' => $property->id,
                'external_id' => 'wrong-guests',
                'check_in' => '2026-10-01',
                'check_out' => '2026-10-05',
                'max_guests' => 1,
                'price' => 60,
                'currency' => 'USD',
            ]);

        Offer::factory()
            ->forImport($import)
            ->create([
                'property_id' => $property->id,
                'external_id' => 'no-units',
                'check_in' => '2026-10-01',
                'check_out' => '2026-10-05',
                'price' => 70,
                'currency' => 'USD',
                'available_units' => 0,
            ]);

        Offer::factory()
            ->forImport($import)
            ->create([
                'property_id' => $property->id,
                'external_id' => 'expired',
                'check_in' => '2026-10-01',
                'check_out' => '2026-10-05',
                'price' => 80,
                'currency' => 'USD',
                'expires_at' => now()->subMinute(),
            ]);

        Offer::factory()
            ->forImport($import)
            ->create([
                'property_id' => $property->id,
                'external_id' => 'wrong-currency',
                'check_in' => '2026-10-01',
                'check_out' => '2026-10-05',
                'price' => 40,
                'currency' => 'EUR',
            ]);

        $response = $this->getJson('/api/properties?' . http_build_query([
                'check_in' => '2026-10-01',
                'check_out' => '2026-10-05',
                'guests' => 2,
                'currency' => 'USD',
            ]));

        $response
            ->assertOk()
            ->assertJsonPath('data.0.best_offer.id', $validOffer->id)
            ->assertJsonPath('data.0.best_offer.price', '150.00');
    }

    public function test_filters_by_city_and_orders_properties_by_best_offer_price(): void
    {
        $supplier = Supplier::factory()->create();

        $import = Import::factory()->create([
            'supplier_id' => $supplier->id,
        ]);

        $kyivCheap = Property::factory()->create([
            'code' => 'kyiv-cheap',
            'city' => 'Kyiv',
        ]);

        $kyivExpensive = Property::factory()->create([
            'code' => 'kyiv-expensive',
            'city' => 'Kyiv',
        ]);

        $lviv = Property::factory()->create([
            'code' => 'lviv',
            'city' => 'Lviv',
        ]);

        Offer::factory()
            ->forImport($import)
            ->create([
                'property_id' => $kyivCheap->id,
                'external_id' => 'kyiv-cheap-offer',
                'check_in' => '2026-10-01',
                'check_out' => '2026-10-05',
                'max_guests' => 2,
                'price' => 100,
                'currency' => 'USD',
                'available_units' => 1,
                'expires_at' => now()->addDay(),
            ]);

        Offer::factory()
            ->forImport($import)
            ->create([
                'property_id' => $kyivExpensive->id,
                'external_id' => 'kyiv-expensive-offer',
                'check_in' => '2026-10-01',
                'check_out' => '2026-10-05',
                'max_guests' => 2,
                'price' => 200,
                'currency' => 'USD',
                'available_units' => 1,
                'expires_at' => now()->addDay(),
            ]);

        Offer::factory()
            ->forImport($import)
            ->create([
                'property_id' => $lviv->id,
                'external_id' => 'lviv-offer',
                'check_in' => '2026-10-01',
                'check_out' => '2026-10-05',
                'max_guests' => 2,
                'price' => 50,
                'currency' => 'USD',
                'available_units' => 1,
                'expires_at' => now()->addDay(),
            ]);

        $response = $this->getJson('/api/properties?' . http_build_query([
                'city' => 'Kyiv',
                'check_in' => '2026-10-01',
                'check_out' => '2026-10-05',
                'guests' => 2,
                'currency' => 'USD',
            ]));

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $kyivCheap->id)
            ->assertJsonPath('data.0.best_offer.price', '100.00')
            ->assertJsonPath('data.1.id', $kyivExpensive->id)
            ->assertJsonPath('data.1.best_offer.price', '200.00');
    }
}