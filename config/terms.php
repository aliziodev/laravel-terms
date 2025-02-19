<?php

return [

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
        'category',
        'tag',
        'size',
        'color',
        'unit',
        'type',
        'brand',
        'model',
        'variant',
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
        
        // Slug separator
        'slug_separator' => env('TERM_SLUG_SEPARATOR', '-'),
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
        // Reserved meta keys that cannot be used
        'reserved_keys' => [
            'id', 'name', 'slug', 'type', 'parent_id', 
            'order', 'created_at', 'updated_at', 'deleted_at'
        ],
    ],
];