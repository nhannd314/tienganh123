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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        // Defaults to the auth.google.callback route; set it when the registered redirect URI differs.
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'azure_speech' => [
        // Speech resource used to score pronunciation (lesson review part 5).
        'key' => env('AZURE_SPEECH_KEY'),
        'region' => env('AZURE_SPEECH_REGION'),
        // Optional: the full speech-to-text URL, for a resource with a custom domain. Defaults to the regional endpoint.
        'endpoint' => env('AZURE_SPEECH_ENDPOINT'),
    ],

    'oxford_learners' => [
        // Oxford Learner's Dictionaries, where a new word's IPA and audio are fetched from. Set false to switch off.
        'enabled' => env('OXFORD_LEARNERS_ENABLED', true),
    ],

    'sepay' => [
        // One of the two protects the webhook; the secret (HMAC-SHA256) wins when both are set.
        'webhook_secret' => env('SEPAY_WEBHOOK_SECRET'),
        'api_key' => env('SEPAY_API_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
