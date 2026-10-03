<?php

declare(strict_types=1);

// Minimal WordPress stand-ins for the provider registry and section service.
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

function sanitize_title(string $title): string
{
    return trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($title)), '-');
}

require dirname(__DIR__) . '/includes/providers.php';
