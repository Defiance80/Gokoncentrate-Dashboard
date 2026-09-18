<?php

/*
|--------------------------------------------------------------------------
| Media Radar configuration
|--------------------------------------------------------------------------
|
| Provider secrets are read from the environment only. They are never
| exposed to the browser, the mobile app or any API response.
|
*/

return [
    'name' => 'MediaRadar',

    'enabled' => env('MEDIA_RADAR_ENABLED', true),

    'scheduler_enabled' => env('MEDIA_RADAR_SCHEDULER_ENABLED', true),

    'queue' => env('MEDIA_RADAR_QUEUE_NAME', 'default'),

    'default_publication_id' => (int) env('MEDIA_RADAR_DEFAULT_PUBLICATION_ID', 1),

    'providers' => [
        'youtube' => [
            'enabled' => env('YOUTUBE_API_ENABLED', true),
            'api_key' => env('YOUTUBE_API_KEY'),
            'project_id' => env('YOUTUBE_API_PROJECT_ID'),
            'base_url' => 'https://www.googleapis.com/youtube/v3',
            // search.list is billed against a small daily "Search Queries" bucket.
            // Kept as configuration so a quota change never needs a code change.
            'daily_search_cap' => (int) env('YOUTUBE_DAILY_SEARCH_CAP', 90),
            'max_results' => (int) env('YOUTUBE_MAX_RESULTS', 25),
            'timeout' => (int) env('YOUTUBE_HTTP_TIMEOUT', 20),
        ],

        'vimeo' => [
            'enabled' => env('VIMEO_API_ENABLED', true),
            'access_token' => env('VIMEO_ACCESS_TOKEN'),
            'client_id' => env('VIMEO_CLIENT_ID'),
            'client_secret' => env('VIMEO_CLIENT_SECRET'),
            'base_url' => 'https://api.vimeo.com',
            'max_results' => (int) env('VIMEO_MAX_RESULTS', 25),
            'timeout' => (int) env('VIMEO_HTTP_TIMEOUT', 20),
        ],

        // Internet Archive — no API key; public-domain / freely-viewable movies.
        // Playback is a direct MP4 resolved from the item metadata.
        'archive' => [
            'enabled' => env('ARCHIVE_API_ENABLED', true),
            'base_url' => 'https://archive.org',
            'max_results' => (int) env('ARCHIVE_MAX_RESULTS', 15),
            'timeout' => (int) env('ARCHIVE_HTTP_TIMEOUT', 25),
        ],

        // Dailymotion — public Data API, no key for search. Playback via the
        // Dailymotion embed iframe (the existing "embedded" player path).
        'dailymotion' => [
            'enabled' => env('DAILYMOTION_API_ENABLED', true),
            'base_url' => 'https://api.dailymotion.com',
            'max_results' => (int) env('DAILYMOTION_MAX_RESULTS', 25),
            'timeout' => (int) env('DAILYMOTION_HTTP_TIMEOUT', 20),
        ],
    ],

    /*
    | Editorial AI. Re-uses the OpenAI key already stored by the dashboard
    | (settings key "ChatGPT_key") through App\Services\ChatGTPService.
    */
    'analysis' => [
        'model_reference' => env('AI_EDITORIAL_MODEL', 'gpt-4o'),
        'version' => 1,
    ],

    /*
    | Cover art. "provider_link" keeps the artwork hosted by YouTube/Vimeo and
    | only stores the link. "cropped_local" additionally renders a poster-ratio
    | crop with GD and stores it beside the other movie artwork.
    */
    'cover_art' => [
        'poster_ratio' => env('MEDIA_RADAR_POSTER_RATIO', '2:3'),
        'poster_width' => (int) env('MEDIA_RADAR_POSTER_WIDTH', 1000),
        'jpeg_quality' => (int) env('MEDIA_RADAR_POSTER_QUALITY', 85),
        'storage_folder' => 'movie',
    ],

    'retry' => [
        // Minutes. Attempt 1 is immediate.
        'backoff' => [1, 5, 30, 120],
        'max_attempts' => 5,
    ],

    'cache' => [
        'provider_video_ttl' => 60 * 60 * 6,
        'health_ttl' => 60 * 60 * 24,
    ],
];
