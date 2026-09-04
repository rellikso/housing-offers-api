<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Offer;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ReservationController extends Controller
{
    public function store(
        StoreReservationRequest $request,
        Offer $offer,
    ): ReservationResource|JsonResponse {
        $reservation = DB::transaction(function () use ($request, $offer) {
            $offer = Offer::query()
                ->whereKey($offer->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($offer->available_units < 1) {
                throw new ConflictHttpException(
                    'Offer is no longer available.',
                );
            }

            $offer->decrement('available_units');

            return Reservation::create([
                'offer_id' => $offer->id,
                'client_reference' => $request->string('client_reference')->toString(),
                'customer_name' => $request->string('customer_name')->toString(),
                'customer_email' => $request->string('customer_email')->toString(),
            ]);
        });

        return (new ReservationResource($reservation))
            ->response()
            ->setStatusCode(201);
    }
}