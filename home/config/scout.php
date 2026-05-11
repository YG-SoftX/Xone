<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Search Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default search driver that will be used by
    | Laravel Scout. Supported: "tntsearch", "algolia", "meilisearch"
    |
    */
    'driver' => env('SCOUT_DRIVER', 'tntsearch'),
    
    /*
    |--------------------------------------------------------------------------
    | TNTSearch Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the TNTSearch engine settings for optimal
    | search performance in the YG Home ecosystem.
    |
    */
    'tntsearch' => [
        'storage' => storage_path('indexes'),
        
        // Enable fuzzy matching for typo tolerance
        'fuzziness' => env('TNTSEARCH_FUZZINESS', true),
        
        // Fuzzy search parameters
        'fuzzy' => [
            'prefix_length' => 2,      // Minimum characters before fuzziness
            'max_expansions' => 50,    // Maximum number of fuzzy matches
            'distance' => 2,           // Levenshtein distance threshold
        ],
        
        // Enable "as-you-type" suggestions
        'asYouType' => false,
        
        // Enable boolean operators (AND, OR, NOT)
        'searchBoolean' => env('TNTSEARCH_BOOLEAN', true),
        
        // Maximum documents to return per query
        'maxDocs' => env('TNTSEARCH_MAX_DOCS', 500),
        
        // Field weights for relevance scoring
        'weights' => [
            'title' => 3.0,      // Title matches are most important
            'content' => 1.5,    // Content matches have medium weight
            'service' => 1.0,    // Service type has base weight
        ],
        
        // Stemming configuration (reduces words to root form)
        'stemmer' => \TeamTNT\TNTSearch\Support\PorterStemmer::class,
        
        // Stop words to ignore during indexing
        'stopwords' => [
            'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to',
            'for', 'of', 'with', 'by', 'from', 'is', 'are', 'was', 'were',
            'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did',
            'will', 'would', 'could', 'should', 'may', 'might', 'can', 'shall',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Chunk Size
    |--------------------------------------------------------------------------
    |
    | When importing or deleting records, Scout will process them in chunks.
    | Adjust these values based on your server's memory capacity.
    |
    */
    'chunk' => [
        'searchable' => 500,   // Records to import at once
        'unsearchable' => 500, // Records to delete at once
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    |
    | If your models use soft deletes, enable this to automatically remove
    | deleted records from the search index.
    |
    */
    'soft_delete' => false,
    
    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Control whether model updates should be queued for indexing.
    | Set to true for better performance with large datasets.
    |
    */
    'queue' => [
        'connection' => null,  // Use default queue connection
        'queue' => 'default',  // Queue name
    ],
];
