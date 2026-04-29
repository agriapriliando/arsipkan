<?php

$reservedSlugs = [
    'superadmin',
    'up',
    'livewire',
    'api',
    'storage',
];

$reservedSlugPattern = implode('|', array_map(
    static fn (string $slug): string => preg_quote($slug, '/'),
    $reservedSlugs,
));

return [
    'route_parameter' => 'tenant_slug',

    'reserved_slugs' => $reservedSlugs,

    'route_slug_pattern' => $reservedSlugPattern === ''
        ? '[a-z0-9]+(?:-[a-z0-9]+)*'
        : '(?!(?:'.$reservedSlugPattern.')$)[a-z0-9]+(?:-[a-z0-9]+)*',

    'default_storage_quota_bytes' => (int) env('TENANCY_DEFAULT_STORAGE_QUOTA_BYTES', 10 * 1024 * 1024 * 1024),

    'default_storage_warning_threshold_percent' => (int) env('TENANCY_DEFAULT_STORAGE_WARNING_THRESHOLD_PERCENT', 80),
];
