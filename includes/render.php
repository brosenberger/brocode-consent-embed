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
 * Click-to-load placeholder shared by the block and the core embed gate. The
 * iframe URL only lives in a data attribute; view.js creates the iframe after
 * consent, so nothing is requested from the third party before that.
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
 * Placeholder for a single iframe embed (the block and core oEmbeds).
 *
 * @param array{provider: string, src: string} $resolved From resolve().
 * @param array{title?: string, notice?: string, height?: int, link?: string, wrapper?: string} $args
 *        wrapper: pre-built attribute string (the block passes get_block_wrapper_attributes()).
 */
function placeholder(array $resolved, array $args = []): string
{
    $provider = providers()[$resolved['provider']] ?? null;
    if (!is_array($provider)) {
        return '';
    }

    $service = [
        'id'       => $resolved['provider'],
        'label'    => (string) ($provider['label'] ?? $resolved['provider']),
        'company'  => (string) ($provider['company'] ?? ''),
        'category' => consent_category((string) ($provider['category'] ?? '')),
    ];
    $args['wrapper'] ??= sprintf('class="bce-embed" style="%s"', esc_attr(placeholder_style($resolved, (int) ($args['height'] ?? 450))));
    $args['link']      = (string) ($args['link'] ?? '') !== '' ? (string) $args['link'] : $resolved['src'];

    return gate($service, $args + ['src' => $resolved['src']]);
}

/**
 * The consent notice around either an iframe URL (src) or inert block markup
 * (content, kept in a <template> until consent).
 *
 * @param array{id: string, label: string, company: string, category: string} $service
 * @param array{title?: string, notice?: string, wrapper: string, src?: string, link?: string, content?: string} $args
 */
function gate(array $service, array $args): string
{
    $label  = $service['label'];
    $title  = (string) ($args['title'] ?? '') !== '' ? (string) $args['title'] : $label;
    $notice = (string) ($args['notice'] ?? '') !== ''
        ? str_replace('%s', $label, (string) $args['notice'])
        /* translators: 1: service name, e.g. YouTube, 2: company receiving the data, e.g. Google */
        : sprintf(__('This content is provided by %1$s. Loading it sends data such as your IP address to %2$s.', 'brocode-consent-embed'), $label, $service['company']);
    $policy = get_privacy_policy_url();
    $link   = (string) ($args['link'] ?? '');

    ob_start();
    ?>
<div <?php echo $args['wrapper']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_attr() / get_block_wrapper_attributes(). ?>
    <?php if (isset($args['src'])) : ?>
    data-bce-src="<?php echo esc_url($args['src']); ?>"
    <?php endif; ?>
    data-bce-provider="<?php echo esc_attr($service['id']); ?>"
    data-bce-category="<?php echo esc_attr($service['category']); ?>"
    data-bce-title="<?php echo esc_attr($title); ?>">
    <div class="bce-embed__notice">
        <p class="bce-embed__heading"><?php echo esc_html($title); ?></p>
        <p class="bce-embed__text">
            <?php echo esc_html($notice); ?>
            <?php if ($policy !== '') : ?>
                <a href="<?php echo esc_url($policy); ?>"><?php esc_html_e('Privacy policy', 'brocode-consent-embed'); ?></a>
            <?php endif; ?>
        </p>
        <p class="bce-embed__actions">
            <button type="button" class="wp-element-button bce-embed__load">
                <?php
                /* translators: %s: service name, e.g. YouTube */
                echo esc_html(sprintf(__('Load %s', 'brocode-consent-embed'), $label));
                ?>
            </button>
        </p>
        <p class="bce-embed__options">
            <label>
                <input type="checkbox" class="bce-embed__remember">
                <?php
                /* translators: %s: service name, e.g. YouTube */
                echo esc_html(sprintf(__('Always load %s on this site', 'brocode-consent-embed'), $label));
                ?>
            </label>
            <?php if ($link !== '') : ?>
            <a href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Open in new tab', 'brocode-consent-embed'); ?></a>
            <?php endif; ?>
        </p>
    </div>
    <?php if (isset($args['content'])) : ?>
    <template class="bce-embed__content"><?php echo $args['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered inner blocks, escaped by their own render. ?></template>
    <?php endif; ?>
</div>
    <?php
    wp_enqueue_script('brocode-consent-embed-view-script');
    wp_enqueue_style('brocode-consent-embed-style');
    return (string) ob_get_clean();
}

/**
 * Sizing for the placeholder and the iframe that replaces it: video providers
 * keep their aspect ratio, everything else uses the editor-chosen height.
 *
 * @param array{provider: string, src: string} $resolved
 */
function placeholder_style(array $resolved, int $height): string
{
    $ratio = providers()[$resolved['provider']]['aspect_ratio'] ?? null;
    return is_string($ratio) && preg_match('#^\d+\s*/\s*\d+$#', $ratio)
        ? '--bce-aspect-ratio:' . $ratio
        : '--bce-height:' . max(200, min(1200, $height)) . 'px';
}
