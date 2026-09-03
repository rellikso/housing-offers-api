<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/properties',
    operationId: 'searchProperties',
    summary: 'Search available properties',
    description: 'Returns properties with the cheapest currently available offer matching the search criteria.',
    tags: ['Properties'],
    parameters: [
        new OA\Parameter(
            name: 'city',
            description: 'Filter properties by city.',
            in: 'query',
            required: false,
            schema: new OA\Schema(
                type: 'string',
            ),
            example: 'Kyiv',
        ),
        new OA\Parameter(
            name: 'check_in',
            description: 'Check-in date.',
            in: 'query',
            required: true,
            schema: new OA\Schema(
                type: 'string',
                format: 'date',
            ),
            example: '2026-10-01',
        ),
        new OA\Parameter(
            name: 'check_out',
            description: 'Check-out date.',
            in: 'query',
            required: true,
            schema: new OA\Schema(
                type: 'string',
                format: 'date',
            ),
            example: '2026-10-05',
        ),
        new OA\Parameter(
            name: 'guests',
            description: 'Number of guests.',
            in: 'query',
            required: true,
            schema: new OA\Schema(
                type: 'integer',
                minimum: 1,
            ),
            example: 2,
        ),
        new OA\Parameter(
            name: 'currency',
            description: 'Currency used to compare offer prices.',
            in: 'query',
            required: true,
            schema: new OA\Schema(
                type: 'string',
                minLength: 3,
                maxLength: 3,
            ),
            example: 'USD',
        ),
        new OA\Parameter(
            name: 'page',
            description: 'Page number.',
            in: 'query',
            required: false,
            schema: new OA\Schema(
                type: 'integer',
                minimum: 1,
            ),
            example: 1,
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Properties found.',
        ),
        new OA\Response(
            response: 422,
            description: 'Validation error.',
        ),
    ],
)]
class Properties
{
}
