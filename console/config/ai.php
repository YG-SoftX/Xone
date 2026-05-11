<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Model Pricing
    |--------------------------------------------------------------------------
    |
    | Cost per token for different AI models (in USD)
    | Prices are per 1,000 tokens
    |
    */

    'pricing' => [
        // OpenAI Models
        'gpt-4' => [
            'prompt' => 0.00003,      // $0.03 per 1K tokens
            'completion' => 0.00006,   // $0.06 per 1K tokens
        ],
        'gpt-4-turbo' => [
            'prompt' => 0.00001,
            'completion' => 0.00003,
        ],
        'gpt-3.5-turbo' => [
            'prompt' => 0.0000015,     // $0.0015 per 1K tokens
            'completion' => 0.000002,  // $0.002 per 1K tokens
        ],

        // Anthropic Models
        'claude-3-opus' => [
            'prompt' => 0.000015,
            'completion' => 0.000075,
        ],
        'claude-3-sonnet' => [
            'prompt' => 0.000003,
            'completion' => 0.000015,
        ],
        'claude-2' => [
            'prompt' => 0.000008,
            'completion' => 0.000024,
        ],

        // Google Models
        'gemini-pro' => [
            'prompt' => 0.0000005,
            'completion' => 0.0000015,
        ],

        // Default fallback
        'default' => [
            'prompt' => 0.00001,
            'completion' => 0.00002,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limits
    |--------------------------------------------------------------------------
    |
    | Maximum requests per minute per project
    |
    */

    'rate_limits' => [
        'free' => 60,        // 60 requests/minute
        'basic' => 300,      // 300 requests/minute
        'pro' => 1000,       // 1000 requests/minute
        'enterprise' => 5000,// 5000 requests/minute
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Limits
    |--------------------------------------------------------------------------
    |
    | Maximum tokens per request
    |
    */

    'token_limits' => [
        'gpt-4' => 8192,
        'gpt-4-turbo' => 128000,
        'gpt-3.5-turbo' => 16385,
        'claude-3-opus' => 200000,
        'claude-3-sonnet' => 200000,
        'claude-2' => 100000,
        'gemini-pro' => 32768,
    ],
];
