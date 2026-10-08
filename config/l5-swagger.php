<?php

return [
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'Beauty Salon Scheduling API',
            ],

            'routes' => [
                'api' => 'api/documentation',
                'docs' => 'api/docs',
                'oauth2_callback' => 'api/oauth2-callback',
            ],

            'paths' => [
                // Scan controllers/resources for OpenAPI attributes.
                'annotations' => [base_path('app')],
                'docs' => storage_path('api-docs'),
                'docs_json' => 'api-docs.json',
                'base' => null,
                'excludes' => [],
            ],

            // Regenerate on request so no build step is required.
            'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', true),
        ],
    ],
];
