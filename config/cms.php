<?php

return [
    'admin_path' => env('CMS_ADMIN_PATH', 'admin'),
    'modules_path' => base_path('modules'),
    'theme' => env('CMS_THEME', 'default'),

    // If true: page body content renders as raw HTML (shortcodes still supported).
    // If false: non-shortcode content is escaped (safer for untrusted inputs).
    'allow_raw_html' => env('CMS_ALLOW_RAW_HTML', true),
];
