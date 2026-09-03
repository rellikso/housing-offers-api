<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

class ImportController extends Controller
{
    #[OA\Post(
        path: '/api/imports',
        summary: 'Import housing offers',
        tags: ['Imports'],
        responses: [
            new OA\Response(
                response: 202,
                description: 'Import accepted',
            ),
        ],
    )]
    public function store()
    {
        //
    }
}
