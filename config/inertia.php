<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Initial Page Object Format
    |--------------------------------------------------------------------------
    |
    | Inertia v2 (PHP) supports two formats for embedding the initial page
    | object in the HTML. The modern format uses a <script type="application/json">
    | element, which is required by @inertiajs/vue3 v3.x+.
    |
    */

    'use_script_element_for_initial_page' => true,

    /*
    |--------------------------------------------------------------------------
    | Server Side Rendering
    |--------------------------------------------------------------------------
    */

    'ssr' => [
        'enabled' => false,
        'url' => 'http://127.0.0.1:13714',
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing
    |--------------------------------------------------------------------------
    */

    'testing' => [
        'ensure_pages_exist' => true,
        'page_paths' => [
            resource_path('js/Pages'),
        ],
        'page_extensions' => [
            'vue',
        ],
    ],

];
