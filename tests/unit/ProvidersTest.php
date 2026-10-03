<?php

declare(strict_types=1);

namespace BroCode\ConsentEmbed\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function BroCode\ConsentEmbed\resolve;

final class ProvidersTest extends TestCase
{
    /**
     * @return array<string, array{string, string, string}>
     */
    public static function acceptedUrls(): array
    {
        return [
            'maps embed' => [
                'https://www.google.com/maps/embed?pb=!1m18!1m12',
                'google-maps',
                'https://www.google.com/maps/embed?pb=!1m18!1m12',
            ],
            'calendar embed' => [
                'https://calendar.google.com/calendar/embed?src=abc%40group.calendar.google.com&ctz=Europe%2FVienna',
                'google-calendar',
                'https://calendar.google.com/calendar/embed?src=abc%40group.calendar.google.com&ctz=Europe%2FVienna',
            ],
            'youtube watch' => [
                'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'youtube',
                'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
            ],
            'youtube short link with start' => [
                'https://youtu.be/dQw4w9WgXcQ?t=42',
                'youtube',
                'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?start=42',
            ],
            'youtube oembed iframe src' => [
                'https://www.youtube.com/embed/dQw4w9WgXcQ?feature=oembed',
                'youtube',
                'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
            ],
            'youtube shorts' => [
                'https://www.youtube.com/shorts/dQw4w9WgXcQ',
                'youtube',
                'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
            ],
            'vimeo page' => [
                'https://vimeo.com/76979871',
                'vimeo',
                'https://player.vimeo.com/video/76979871?dnt=1',
            ],
            'vimeo unlisted with hash' => [
                'https://vimeo.com/76979871/abc123def4',
                'vimeo',
                'https://player.vimeo.com/video/76979871?dnt=1&h=abc123def4',
            ],
            'vimeo player' => [
                'https://player.vimeo.com/video/76979871?h=abc123def4&app_id=1',
                'vimeo',
                'https://player.vimeo.com/video/76979871?dnt=1&h=abc123def4',
            ],
            'openstreetmap embed' => [
                'https://www.openstreetmap.org/export/embed.html?bbox=14.0,48.1,14.1,48.2&layer=mapnik',
                'openstreetmap',
                'https://www.openstreetmap.org/export/embed.html?bbox=14.0,48.1,14.1,48.2&layer=mapnik',
            ],
        ];
    }

    #[DataProvider('acceptedUrls')]
    public function testResolvesKnownProviders(string $url, string $provider, string $src): void
    {
        self::assertSame(['provider' => $provider, 'src' => $src], resolve($url));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function rejectedUrls(): array
    {
        return [
            'empty' => [''],
            'maps over http' => ['http://www.google.com/maps/embed?pb=1'],
            'maps page, not embed' => ['https://www.google.com/maps/place/Wels'],
            'lookalike host' => ['https://www.google.com.evil.test/maps/embed?pb=1'],
            'javascript scheme' => ['javascript:alert(1)//www.youtube.com/watch?v=dQw4w9WgXcQ'],
            'youtube id too short' => ['https://www.youtube.com/watch?v=abc'],
            'youtube id with markup' => ['https://youtu.be/dQw4w9WgX"><'],
            'vimeo non-numeric' => ['https://vimeo.com/channels/staffpicks'],
            'unknown host' => ['https://example.com/embed/1'],
        ];
    }

    #[DataProvider('rejectedUrls')]
    public function testRejectsEverythingElse(string $url): void
    {
        self::assertNull(resolve($url));
    }

    public function testFilterCanAddProvider(): void
    {
        $GLOBALS['bce_test_filters']['brocode_consent_embed_providers'] = static function (array $providers): array {
            $providers['example'] = [
                'label'     => 'Example',
                'company'   => 'Example Inc.',
                'category'  => 'marketing',
                'embed_url' => static fn (array $parts): ?string => ($parts['host'] ?? '') === 'example.com' ? 'https://example.com/e' : null,
            ];
            return $providers;
        };

        try {
            self::assertSame(['provider' => 'example', 'src' => 'https://example.com/e'], resolve('https://example.com/x'));
        } finally {
            unset($GLOBALS['bce_test_filters']['brocode_consent_embed_providers']);
        }
    }
}
