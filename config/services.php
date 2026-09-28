<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'map_key' => env('GOOGLE_MAPS_API_KEY', 'AIzaSyCbb_HYYcDgCFtwhzn98boM5Fw_0W84Ze4'),
    ],

    'firebase' => [
        'api_key' => env('FIREBASE_API_KEY', 'AIzaSyDJHyKxNOWHweOjbhZGsnIUN7xJ0zViv4w'),
        'project_id' => env('FIREBASE_PROJECT_ID', 'on-demand-3834d'),
        'app_id' => env('FIREBASE_APP_ID', '1:892839896492:web:651a769b7434992c750609'),
        'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID', '892839896492'),
        'storage_bucket' => env('FIREBASE_STORAGE_BUCKET', 'on-demand-3834d.firebasestorage.app'),
    ],

];
