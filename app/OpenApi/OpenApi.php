<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Housing Offers API',
    description: 'REST API for importing, searching and reserving housing offers.',
)]
#[OA\Server(
    url: '/',
    description: 'Local environment',
)]
class OpenApi
{
}
