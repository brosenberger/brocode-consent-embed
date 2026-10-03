<?php

declare(strict_types=1);

// Minimal WordPress stand-ins: the provider registry only needs these three.
define('ABSPATH', __DIR__ . '/');

function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
{
    $filter = $GLOBALS['bce_test_filters'][$hook] ?? null;
    return $filter ? $filter($value, ...$args) : $value;
}

function __(string $text, string $domain = 'default'): string
{
    return $text;
}

/**
 * @return array<string, mixed>|false
 */
function wp_parse_url(string $url): array|false
{
    return parse_url($url);
}

require dirname(__DIR__) . '/includes/providers.php';
