<?php

// Defaults App\Support\Seo fills in. See docs/features/seo-and-ssr.md.
return [

    // Falls back to APP_NAME; SystemSettings::app_name wins at runtime (see Seo).
    'site_name' => env('SEO_SITE_NAME', env('APP_NAME', 'Laravel')),

    // Appended to titles as "<title> · <suffix>". Null = none.
    'title_suffix' => env('SEO_TITLE_SUFFIX', null),

    'default_title' => env('SEO_DEFAULT_TITLE', env('APP_NAME', 'Laravel')),

    'default_description' => env('SEO_DEFAULT_DESCRIPTION', ''),

    // Absolute URL or app-relative path; null falls back to the brand logo.
    'default_image' => env('SEO_DEFAULT_IMAGE', null),

    'locale' => env('SEO_LOCALE', 'en_US'),

    'twitter_card' => 'summary_large_image',
    'twitter_site' => env('SEO_TWITTER_SITE', null),

];
