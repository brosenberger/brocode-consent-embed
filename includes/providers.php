<?php
/**
 * Copyright (C) 2026 Benjamin Rosenberger <bensch.rosenberger@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * Provider registry: maps a pasted URL to a known third party and the iframe
 * URL that may be loaded once the visitor consents. Anything that does not
 * resolve here is never rendered, so this list doubles as the iframe allow-list.
 *
 * @copyright 2026 Benjamin Rosenberger
 * @author bensch.rosenberger@gmail.com
 * @license MIT
 * @link https://brocode.at
 */

declare(strict_types=1);

namespace BroCode\ConsentEmbed;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registered providers, keyed by id.
 *
 * Each entry: label, company (who receives the visitor's data), category
 * (WP Consent API category), optional aspect_ratio (CSS value, for video) and
 * embed_url — a callable receiving the wp_parse_url() parts and the raw URL,
 * returning the iframe src or null when the URL is not one of its own.
 *
 * Third-party filters can return any shape, so callers re-check what they use.
 *
 * @return array<string, array<string, mixed>>
 */
function providers(): array
{
    $providers = [
        'google-maps' => [
            'label'     => __('Google Maps', 'brocode-consent-embed'),
            'company'   => 'Google',
            'category'  => 'marketing',
            'embed_url' => static fn (array $p, string $url): ?string => is_https($p)
                && in_array($p['host'], ['www.google.com', 'google.com', 'maps.google.com'], true)
                && str_starts_with($p['path'] ?? '', '/maps/embed') ? $url : null,
        ],
        'google-calendar' => [
            'label'     => __('Google Calendar', 'brocode-consent-embed'),
            'company'   => 'Google',
            'category'  => 'marketing',
            'embed_url' => static fn (array $p, string $url): ?string => is_https($p)
                && $p['host'] === 'calendar.google.com'
                && str_starts_with($p['path'] ?? '', '/calendar/embed') ? $url : null,
        ],
        'youtube' => [
            'label'        => __('YouTube', 'brocode-consent-embed'),
            'company'      => 'Google',
            'category'     => 'marketing',
            'aspect_ratio' => '16 / 9',
            'embed_url'    => __NAMESPACE__ . '\\youtube_embed_url',
        ],
        'vimeo' => [
            'label'        => __('Vimeo', 'brocode-consent-embed'),
            'company'      => 'Vimeo',
            'category'     => 'marketing',
            'aspect_ratio' => '16 / 9',
            'embed_url'    => __NAMESPACE__ . '\\vimeo_embed_url',
        ],
        'openstreetmap' => [
            'label'     => __('OpenStreetMap', 'brocode-consent-embed'),
            'company'   => 'OpenStreetMap Foundation',
            'category'  => 'functional',
            'embed_url' => static fn (array $p, string $url): ?string => is_https($p)
                && $p['host'] === 'www.openstreetmap.org'
                && ($p['path'] ?? '') === '/export/embed.html' ? $url : null,
        ],
    ];

    /**
     * Filters the consent-gated embed providers.
     *
     * @param array<string, array{label: string, company: string, category: string, aspect_ratio?: string, embed_url: callable}> $providers
     */
    return (array) apply_filters('brocode_consent_embed_providers', $providers);
}

/**
 * Resolves a URL to its provider id and consent-gated iframe src.
 *
 * @return array{provider: string, src: string}|null
 */
function resolve(string $url): ?array
{
    $parts = wp_parse_url(trim($url));
    if (!is_array($parts) || empty($parts['host']) || !in_array($parts['scheme'] ?? '', ['http', 'https'], true)) {
        return null;
    }
    $parts['host'] = strtolower($parts['host']);

    foreach (providers() as $id => $provider) {
        $src = is_callable($provider['embed_url'] ?? null) ? ($provider['embed_url'])($parts, $url) : null;
        if (is_string($src) && $src !== '') {
            return ['provider' => (string) $id, 'src' => $src];
        }
    }
    return null;
}

/**
 * @param array<string, mixed> $parts
 */
function is_https(array $parts): bool
{
    return ($parts['scheme'] ?? '') === 'https';
}

/**
 * Any YouTube URL form → privacy-enhanced (youtube-nocookie) embed URL.
 *
 * @param array<string, mixed> $parts
 */
function youtube_embed_url(array $parts): ?string
{
    $host  = (string) $parts['host'];
    $path  = (string) ($parts['path'] ?? '');
    parse_str((string) ($parts['query'] ?? ''), $query);

    if ($host === 'youtu.be') {
        $id = ltrim($path, '/');
    } elseif (in_array($host, ['www.youtube.com', 'youtube.com', 'm.youtube.com', 'www.youtube-nocookie.com', 'youtube-nocookie.com'], true)) {
        $id = $path === '/watch'
            ? (string) ($query['v'] ?? '')
            : (string) preg_replace('#^/(?:embed|shorts|live)/#', '', $path, 1, $matched);
        if ($path !== '/watch' && empty($matched)) {
            return null;
        }
    } else {
        return null;
    }

    if (!preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) {
        return null;
    }

    $start = (string) ($query['start'] ?? $query['t'] ?? '');
    $start = preg_match('/^(\d+)s?$/', $start, $m) ? (int) $m[1] : 0;

    return 'https://www.youtube-nocookie.com/embed/' . $id . ($start > 0 ? '?start=' . $start : '');
}

/**
 * Vimeo page or player URL → player URL with Do-Not-Track.
 *
 * @param array<string, mixed> $parts
 */
function vimeo_embed_url(array $parts): ?string
{
    $host = (string) $parts['host'];
    $path = (string) ($parts['path'] ?? '');
    parse_str((string) ($parts['query'] ?? ''), $query);

    if (in_array($host, ['vimeo.com', 'www.vimeo.com'], true)) {
        $ok = preg_match('#^/(\d+)(?:/([A-Za-z0-9]+))?/?$#', $path, $m);
    } elseif ($host === 'player.vimeo.com') {
        $ok = preg_match('#^/video/(\d+)/?$#', $path, $m);
    } else {
        return null;
    }
    if (!$ok) {
        return null;
    }

    $hash = (string) ($m[2] ?? $query['h'] ?? '');
    $hash = preg_match('/^[A-Za-z0-9]+$/', $hash) ? $hash : '';

    return 'https://player.vimeo.com/video/' . $m[1] . '?dnt=1' . ($hash !== '' ? '&h=' . $hash : '');
}
