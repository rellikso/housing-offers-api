<?php

namespace App\Jobs;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Models\Offer;
use App\Models\Property;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessImport implements ShouldQueue
{
    use Queueable;

    private const CHUNK_SIZE = 100;

    public function __construct(
        public Import $import,
        public array $offers,
    ) {
    }

    public function handle(): void
    {
        $this->import->update([
            'status' => ImportStatus::Processing,
            'processed_offers' => 0,
            'error' => null,
        ]);

        try {
            foreach (array_chunk($this->offers, self::CHUNK_SIZE) as $chunk) {
                $this->processChunk($chunk);
            }

            $this->import->update([
                'status' => ImportStatus::Completed,
                'completed_at' => now(),
            ]);
        } catch (Throwable $e) {
            $this->import->update([
                'status' => ImportStatus::Failed,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function processChunk(array $offers): void
    {
        DB::transaction(function () use ($offers): void {
            $offerRows = [];

            foreach ($offers as $offer) {
                $property = Property::updateOrCreate(
                    [
                        'code' => $offer['property']['code'],
                    ],
                    [
                        'name' => $offer['property']['name'],
                        'city' => $offer['property']['city'],
                    ],
                );

                $offerRows[] = [
                    'supplier_id' => $this->import->supplier_id,
                    'property_id' => $property->id,
                    'import_id' => $this->import->id,
                    'external_id' => $offer['external_id'],
                    'check_in' => $offer['check_in'],
                    'check_out' => $offer['check_out'],
                    'max_guests' => $offer['max_guests'],
                    'price' => $offer['price'],
                    'currency' => $offer['currency'],
                    'available_units' => $offer['available_units'],
                    'expires_at' => Carbon::parse($offer['expires_at']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Offer::upsert(
                $offerRows,
                ['supplier_id', 'external_id'],
                [
                    'property_id',
                    'import_id',
                    'check_in',
                    'check_out',
                    'max_guests',
                    'price',
                    'currency',
                    'available_units',
                    'expires_at',
                    'updated_at',
                ],
            );
        });

        $this->import->increment(
            'processed_offers',
            count($offers),
        );
    }
}
