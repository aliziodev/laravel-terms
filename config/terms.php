<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Term Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default term "driver" that will be used by the
    | framework. You may set this to any of the connections defined in the
    | "drivers" array below.
    |
    */
    'default' => env('TERM_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Term Drivers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the term drivers for your application. By default,
    | the database driver is used. You can add custom drivers as needed.
    |
    */
    'drivers' => [
        'database' => [
            'driver' => 'database',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Tables
    |--------------------------------------------------------------------------
    |
    | Here you may specify which table names should be used for terms and
    | termables (the pivot table for polymorphic relationships).
    |
    */
    'table_names' => [
        'terms' => 'terms',
        'termables' => 'termables',
    ],

    /*
    |--------------------------------------------------------------------------
    | Term Types
    |--------------------------------------------------------------------------
    |
    | Here you may specify the available term types in your application.
    | You can add custom types and their configurations.
    |
    */
    'types' => [
        'category' => [
            'name' => 'Categories',
            'hierarchical' => true,
            'max_depth' => 3,
        ],
        'tag' => [
            'name' => 'Tags',
            'hierarchical' => false,
            'max_depth' => 1,
        ],
        'size' => [
            'name' => 'Sizes',
            'hierarchical' => false,
            'max_depth' => 1,
        ],
        'color' => [
            'name' => 'Colors',
            'hierarchical' => false,
            'max_depth' => 1,
        ],
        'unit' => [
            'name' => 'Units',
            'hierarchical' => false,
            'max_depth' => 1,
        ],
        'type' => [
            'name' => 'Types',
            'hierarchical' => false,
            'max_depth' => 1,
        ],
        'brand' => [
            'name' => 'Brands',
            'hierarchical' => false,
            'max_depth' => 1,
        ],
        'model' => [
            'name' => 'Models',
            'hierarchical' => false,
            'max_depth' => 1,
        ],  
        // Add your custom types here
    ],

    /*
    |--------------------------------------------------------------------------
    | Term Settings
    |--------------------------------------------------------------------------
    |
    | Various settings for term behavior.
    |
    */
    'settings' => [
        // Whether to cascade delete children when a parent term is deleted
        'cascade_delete' => env('TERM_CASCADE_DELETE', false),
        
        // Whether to use unique slugs across all types
        'unique_slugs' => env('TERM_UNIQUE_SLUGS', true),
        
        // Maximum allowed depth for hierarchical terms
        'max_depth' => env('TERM_MAX_DEPTH', 5),
        
        // Whether to auto-generate slugs from names
        'auto_slug' => env('TERM_AUTO_SLUG', true),
        
        // Slug separator
        'slug_separator' => env('TERM_SLUG_SEPARATOR', '-'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Term Validation
    |--------------------------------------------------------------------------
    |
    | Configure validation rules for terms.
    |
    */
    'validation' => [
        'name' => [
            'min' => 2,
            'max' => 255,
        ],
        'slug' => [
            'min' => 2,
            'max' => 255,
            'pattern' => '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        ],
        'description' => [
            'max' => 1000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Term Meta
    |--------------------------------------------------------------------------
    |
    | Configure meta data settings for terms.
    |
    */
    'meta' => [
        // Whether to enable meta data for terms
        'enabled' => true,
        
        // Reserved meta keys that cannot be used
        'reserved_keys' => [
            'id', 'name', 'slug', 'type', 'parent_id', 
            'order', 'created_at', 'updated_at', 'deleted_at'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Term Ordering
    |--------------------------------------------------------------------------
    |
    | Configure how terms should be ordered by default.
    |
    */
    'ordering' => [
        // Default ordering column
        'column' => 'order',
        
        // Default direction
        'direction' => 'asc',
        
        // Whether to auto-assign order values
        'auto_order' => true,
        
        // Starting order number
        'start_position' => 1,
    ],
];