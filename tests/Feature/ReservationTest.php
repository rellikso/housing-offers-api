<?php

namespace Tests\Feature;

use App\Models\Import;
use App\Models\Offer;
use App\Models\Reservation;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_reserve_available_offer(): void
    {
        $supplier = Supplier::factory()->create();

        $import = Import::factory()
            ->for($supplier)
            ->create();

        $offer = Offer::factory()
            ->forImport($import)
            ->create([
                'available_units' => 2,
            ]);

        $response = $this->postJson(
            "/api/offers/{$offer->id}/reservations",
            [
                'client_reference' => 'booking-001',
                'customer_name' => 'John Doe',
                'customer_email' => 'john@example.com',
            ],
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.client_reference',
                'booking-001',
            );

        $this->assertDatabaseHas('reservations', [
            'offer_id' => $offer->id,
            'client_reference' => 'booking-001',
        ]);

        $this->assertDatabaseHas('offers', [
            'id' => $offer->id,
            'available_units' => 1,
        ]);
    }

    public function test_cannot_reserve_unavailable_offer(): void
    {
        $supplier = Supplier::factory()->create();

        $import = Import::factory()
            ->for($supplier)
            ->create();

        $offer = Offer::factory()
            ->forImport($import)
            ->create([
                'available_units' => 0,
            ]);

        $response = $this->postJson(
            "/api/offers/{$offer->id}/reservations",
            [
                'client_reference' => 'booking-002',
                'customer_name' => 'John Doe',
                'customer_email' => 'john@example.com',
            ],
        );

        $response
            ->assertStatus(409);

        $this->assertDatabaseMissing('reservations', [
            'offer_id' => $offer->id,
            'client_reference' => 'booking-002',
        ]);

        $this->assertDatabaseHas('offers', [
            'id' => $offer->id,
            'available_units' => 0,
        ]);
    }
}