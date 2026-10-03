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
 * Server render for brocode/consent-embed. Unknown URLs render nothing on the
 * front end and a hint in the editor preview.
 *
 * @var array<string, mixed> $attributes
 *
 * @copyright 2026 Benjamin Rosenberger
 * @author bensch.rosenberger@gmail.com
 * @license MIT
 * @link https://brocode.at
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$brocode_consent_embed = \BroCode\ConsentEmbed\resolve((string) ($attributes['url'] ?? ''));

if ($brocode_consent_embed === null) {
    if (wp_is_serving_rest_request()) {
        echo '<p>' . esc_html__('Paste a Google Maps, Google Calendar, YouTube, Vimeo or OpenStreetMap link.', 'brocode-consent-embed') . '</p>';
    }
    return;
}

$brocode_consent_embed_height = (int) ($attributes['height'] ?? 450);

$brocode_consent_embed_html = \BroCode\ConsentEmbed\placeholder(
    $brocode_consent_embed,
    [
        'title'   => (string) ($attributes['title'] ?? ''),
        'notice'  => (string) ($attributes['notice'] ?? ''),
        'height'  => $brocode_consent_embed_height,
        'link'    => (string) $attributes['url'],
        'wrapper' => get_block_wrapper_attributes([
            'class' => 'bce-embed',
            'style' => \BroCode\ConsentEmbed\placeholder_style($brocode_consent_embed, $brocode_consent_embed_height),
        ]),
    ]
);

echo $brocode_consent_embed_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside placeholder().
