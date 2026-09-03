<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'city' => $this->city,

            'best_offer' => [
                'id' => $this->offer_id,
                'check_in' => $this->offer_check_in,
                'check_out' => $this->offer_check_out,
                'max_guests' => $this->offer_max_guests,
                'price' => $this->offer_price,
                'currency' => $this->offer_currency,
                'available_units' => $this->offer_available_units,
                'expires_at' => $this->offer_expires_at,
            ],
        ];
    }
}