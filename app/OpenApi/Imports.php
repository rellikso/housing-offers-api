<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/api/imports',
    operationId: 'createImport',
    summary: 'Create an asynchronous offers import',
    tags: ['Imports'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: [
                'supplier',
                'external_import_id',
                'sent_at',
                'offers',
            ],
            properties: [
                new OA\Property(
                    property: 'supplier',
                    type: 'string',
                    example: 'supplier-a',
                ),
                new OA\Property(
                    property: 'external_import_id',
                    type: 'string',
                    example: 'import-12345',
                ),
                new OA\Property(
                    property: 'sent_at',
                    type: 'string',
                    format: 'date-time',
                    example: '2026-09-03T08:00:00Z',
                ),
                new OA\Property(
                    property: 'offers',
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        required: [
                            'external_id',
                            'property',
                            'check_in',
                            'check_out',
                            'max_guests',
                            'price',
                            'currency',
                            'available_units',
                            'expires_at',
                        ],
                        properties: [
                            new OA\Property(
                                property: 'external_id',
                                type: 'string',
                                example: 'offer-123',
                            ),
                            new OA\Property(
                                property: 'property',
                                type: 'object',
                                required: ['code', 'name', 'city'],
                                properties: [
                                    new OA\Property(
                                        property: 'code',
                                        type: 'string',
                                        example: 'hotel-001',
                                    ),
                                    new OA\Property(
                                        property: 'name',
                                        type: 'string',
                                        example: 'Grand Hotel',
                                    ),
                                    new OA\Property(
                                        property: 'city',
                                        type: 'string',
                                        example: 'Kyiv',
                                    ),
                                ],
                            ),
                            new OA\Property(
                                property: 'check_in',
                                type: 'string',
                                format: 'date',
                                example: '2026-10-01',
                            ),
                            new OA\Property(
                                property: 'check_out',
                                type: 'string',
                                format: 'date',
                                example: '2026-10-05',
                            ),
                            new OA\Property(
                                property: 'max_guests',
                                type: 'integer',
                                example: 2,
                            ),
                            new OA\Property(
                                property: 'price',
                                type: 'number',
                                format: 'float',
                                example: 150.00,
                            ),
                            new OA\Property(
                                property: 'currency',
                                type: 'string',
                                example: 'USD',
                            ),
                            new OA\Property(
                                property: 'available_units',
                                type: 'integer',
                                example: 3,
                            ),
                            new OA\Property(
                                property: 'expires_at',
                                type: 'string',
                                format: 'date-time',
                                example: '2026-09-03T12:00:00Z',
                            ),
                        ],
                    ),
                ),
            ],
        ),
    ),
    responses: [
        new OA\Response(
            response: 202,
            description: 'Import accepted for asynchronous processing',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'object',
                        properties: [
                            new OA\Property(
                                property: 'id',
                                type: 'integer',
                                example: 1,
                            ),
                            new OA\Property(
                                property: 'status',
                                type: 'string',
                                example: 'pending',
                            ),
                        ],
                    ),
                ],
            ),
        ),
        new OA\Response(
            response: 422,
            description: 'Validation error',
        ),
    ],
)]
class Imports
{
}