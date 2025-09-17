<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Email Analysis Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for AI-powered email analysis features including
    | summarization, priority classification, and action item extraction.
    */

    // Enable/disable AI analysis
    'enabled' => env('AI_ANALYSIS_ENABLED', true),
    
    // AI service settings
    'service' => [
        'provider' => 'openai', // Currently only OpenAI is supported
        'model' => env('AI_MODEL', 'gpt-3.5-turbo'),
        'max_tokens' => env('AI_MAX_TOKENS', 150),
        'temperature' => env('AI_TEMPERATURE', 0.3),
        'timeout' => env('AI_TIMEOUT', 30),
    ],
    
    // Cache settings for AI analysis results
    'cache' => [
        'enabled' => env('AI_CACHE_ENABLED', true),
        'ttl' => env('AI_CACHE_TTL', 3600), // 1 hour
        'prefix' => 'ai_analysis:',
    ],
    
    // Analysis features
    'features' => [
        'summarization' => env('AI_SUMMARIZATION_ENABLED', true),
        'priority_classification' => env('AI_PRIORITY_ENABLED', true),
        'category_detection' => env('AI_CATEGORY_ENABLED', true),
        'action_items' => env('AI_ACTION_ITEMS_ENABLED', true),
        'sentiment_analysis' => env('AI_SENTIMENT_ENABLED', true),
        'response_detection' => env('AI_RESPONSE_DETECTION_ENABLED', true),
    ],
    
    // Fallback behavior when AI is unavailable
    'fallback' => [
        'enabled' => true,
        'use_keywords' => true,
        'default_priority' => 'medium',
        'default_category' => 'personal',
    ],
    
    // Rate limiting
    'rate_limit' => [
        'requests_per_minute' => env('AI_RATE_LIMIT', 20),
        'burst_limit' => env('AI_BURST_LIMIT', 5),
    ],
    
    // Cost tracking (optional)
    'cost_tracking' => [
        'enabled' => env('AI_COST_TRACKING_ENABLED', false),
        'log_requests' => env('AI_LOG_REQUESTS', false),
        'monthly_limit' => env('AI_MONTHLY_LIMIT', 10.00), // $10 USD
    ],
];
