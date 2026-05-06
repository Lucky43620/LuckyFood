<?php

return [
    'consumer_key' => env('FATSECRET_CONSUMER_KEY', ''),
    'consumer_secret' => env('FATSECRET_CONSUMER_SECRET', ''),
    'base_url' => env('FATSECRET_BASE_URL', 'https://platform.fatsecret.com/rest'),
    'connect_timeout' => (float) env('FATSECRET_CONNECT_TIMEOUT', 3),
    'request_timeout' => (float) env('FATSECRET_REQUEST_TIMEOUT', 8),
    'category_cache_ttl' => (int) env('FATSECRET_CATEGORY_CACHE_TTL', 86400),
];
