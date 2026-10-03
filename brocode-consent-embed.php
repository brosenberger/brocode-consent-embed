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
 * Plugin Name:       BroCode Consent Embed
 * Plugin URI:        https://brocode.at/modules/brocode-consent-embed/
 * Description:       Click-to-load embeds for Google Maps, Google Calendar, YouTube, Vimeo and OpenStreetMap, plus a consent section that holds back any blocks until the visitor agrees. Also gates existing YouTube and Vimeo embeds, and honours the WP Consent API.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Benjamin Rosenberger
 * Author URI:        https://brocode.at
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       brocode-consent-embed
 * Domain Path:       /languages
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

require_once __DIR__ . '/includes/providers.php';
require_once __DIR__ . '/includes/render.php';

add_action('init', __NAMESPACE__ . '\\register');
add_filter('embed_oembed_html', __NAMESPACE__ . '\\gate_oembed', 20, 2);
add_filter('wp_consent_api_registered_' . plugin_basename(__FILE__), '__return_true');

function register(): void
{
    load_plugin_textdomain('brocode-consent-embed', false, dirname(plugin_basename(__FILE__)) . '/languages');
    // consent-embed first: it registers the view script and style both blocks use.
    register_block_type(__DIR__ . '/build/consent-embed');
    register_block_type(__DIR__ . '/build/consent-section');

    $providers = [];
    foreach (providers() as $id => $provider) {
        $providers[] = ['value' => (string) $id, 'label' => (string) ($provider['label'] ?? $id)];
    }
    wp_add_inline_script(
        'brocode-consent-section-editor-script',
        'window.brocodeConsentEmbed = ' . wp_json_encode(['providers' => $providers]) . ';',
        'before'
    );

    foreach (['brocode-consent-embed-editor-script', 'brocode-consent-section-editor-script'] as $handle) {
        wp_set_script_translations($handle, 'brocode-consent-embed', __DIR__ . '/languages');
    }
}

/**
 * Replaces the iframe of an oEmbed (core Embed block or a bare URL in classic
 * content) with the click-to-load placeholder when it belongs to a known provider.
 */
function gate_oembed(mixed $html, mixed $url): mixed
{
    if (!is_string($html) || !is_string($url) || is_admin() || is_feed() || wp_is_serving_rest_request()) {
        return $html;
    }
    if (!preg_match('/<iframe\b[^>]*\bsrc=(["\'])(.*?)\1/i', $html, $src)) {
        return $html;
    }

    $resolved = resolve(html_entity_decode($src[2]));

    /**
     * Filters whether an oEmbed of a known provider is replaced by the consent placeholder.
     *
     * @param bool                                     $gate     Default true when a provider matched.
     * @param array{provider: string, src: string}|null $resolved Matched provider and iframe src.
     * @param string                                   $url      The URL the author embedded.
     */
    if (!apply_filters('brocode_consent_embed_gate_oembed', $resolved !== null, $resolved, $url) || $resolved === null) {
        return $html;
    }

    $title = preg_match('/<iframe\b[^>]*\btitle=(["\'])(.*?)\1/i', $html, $t) ? html_entity_decode($t[2]) : '';

    return placeholder($resolved, ['title' => $title, 'link' => $url]);
}
