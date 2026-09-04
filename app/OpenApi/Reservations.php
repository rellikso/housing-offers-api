<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/api/offers/{offer}/reservations',
    operationId: 'createReservation',
    summary: 'Reserve an offer',
    description: 'Creates a reservation for an available offer. The offer availability is locked during the transaction to prevent concurrent reservations of the last available unit.',
    tags: ['Reservations'],
    parameters: [
        new OA\Parameter(
            name: 'offer',
            description: 'Offer ID.',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer'),
            example: 3,
        ),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: [
                'client_reference',
                'customer_name',
                'customer_email',
            ],
            properties: [
                new OA\Property(
                    property: 'client_reference',
                    type: 'string',
                    example: 'booking-12345',
                ),
                new OA\Property(
                    property: 'customer_name',
                    type: 'string',
                    example: 'John Doe',
                ),
                new OA\Property(
                    property: 'customer_email',
                    type: 'string',
                    format: 'email',
                    example: 'john@example.com',
                ),
            ],
        ),
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Reservation created.',
        ),
        new OA\Response(
            response: 404,
            description: 'Offer not found.',
        ),
        new OA\Response(
            response: 409,
            description: 'Offer is no longer available.',
        ),
        new OA\Response(
            response: 422,
            description: 'Validation error.',
        ),
    ],
)]
class Reservations
{
}