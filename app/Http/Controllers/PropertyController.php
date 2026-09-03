<?php

namespace App\Http\Controllers;

use App\Http\Requests\PropertySearchRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Offer;
use App\Models\Property;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class PropertyController extends Controller
{
    public function index(
        PropertySearchRequest $request,
    ): AnonymousResourceCollection {
        $offers = Offer::query()
            ->select([
                'offers.id',
                'offers.property_id',
                'offers.check_in',
                'offers.check_out',
                'offers.max_guests',
                'offers.price',
                'offers.currency',
                'offers.available_units',
                'offers.expires_at',
            ])
            ->selectRaw('
                ROW_NUMBER() OVER (
                    PARTITION BY property_id
                    ORDER BY price ASC, id ASC
                ) AS offer_rank
            ')
            ->where('currency', $request->string('currency')->toString())
            ->where('check_in', $request->date('check_in'))
            ->where('check_out', $request->date('check_out'))
            ->where('max_guests', '>=', $request->integer('guests'))
            ->where('available_units', '>', 0)
            ->where('expires_at', '>', now());

        $bestOffers = DB::query()
            ->fromSub($offers, 'ranked_offers')
            ->where('offer_rank', 1);

        $properties = Property::query()
            ->joinSub(
                $bestOffers,
                'best_offers',
                fn ($join) => $join->on(
                    'best_offers.property_id',
                    '=',
                    'properties.id',
                ),
            )
            ->select([
                'properties.id',
                'properties.code',
                'properties.name',
                'properties.city',

                'best_offers.id as offer_id',
                'best_offers.check_in as offer_check_in',
                'best_offers.check_out as offer_check_out',
                'best_offers.max_guests as offer_max_guests',
                'best_offers.price as offer_price',
                'best_offers.currency as offer_currency',
                'best_offers.available_units as offer_available_units',
                'best_offers.expires_at as offer_expires_at',
            ]);

        if ($request->filled('city')) {
            $properties->where(
                'properties.city',
                $request->string('city')->toString(),
            );
        }

        $properties
            ->orderBy('best_offers.price')
            ->orderBy('properties.id');

        return PropertyResource::collection(
            $properties->paginate(15),
        );
    }
}
