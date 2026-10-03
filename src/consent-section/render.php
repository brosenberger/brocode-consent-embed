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
 * Server render for brocode/consent-section. The inner blocks go into an inert
 * <template>; without a named service nothing renders, because ungated output
 * would defeat the block.
 *
 * @var array<string, mixed> $attributes
 * @var string               $content
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

$brocode_consent_embed_section = \BroCode\ConsentEmbed\section_service($attributes);
if ($brocode_consent_embed_section === null) {
    return;
}

$brocode_consent_embed_section_height = (int) ($attributes['minHeight'] ?? 0);

$brocode_consent_embed_section_html = \BroCode\ConsentEmbed\gate(
    $brocode_consent_embed_section,
    [
        'title'   => (string) ($attributes['title'] ?? ''),
        'notice'  => (string) ($attributes['notice'] ?? ''),
        'content' => (string) $content,
        'wrapper' => get_block_wrapper_attributes([
            'class' => 'bce-embed bce-embed--section',
            'style' => $brocode_consent_embed_section_height > 0 ? '--bce-height:' . min(1200, $brocode_consent_embed_section_height) . 'px' : '',
        ]),
    ]
);

echo $brocode_consent_embed_section_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside gate().
