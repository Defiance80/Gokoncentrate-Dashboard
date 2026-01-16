<?php
return [
    'secret_key' => env('VIDEO_SECRET_KEY', 'my-default-secret'),
    'max_age'    => env('VIDEO_MAX_AGE', 86400), // 24 hours
];