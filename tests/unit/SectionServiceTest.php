<?php

declare(strict_types=1);

namespace BroCode\ConsentEmbed\Tests\Unit;

use PHPUnit\Framework\TestCase;

use function BroCode\ConsentEmbed\section_service;

final class SectionServiceTest extends TestCase
{
    public function testRegisteredProviderComesFromTheRegistry(): void
    {
        $service = section_service(['provider' => 'youtube', 'serviceName' => 'ignored', 'company' => 'ignored', 'category' => 'statistics']);

        self::assertSame(['id' => 'youtube', 'label' => 'YouTube', 'company' => 'Google', 'category' => 'marketing'], $service);
    }

    public function testCustomServiceGetsAStableStorageId(): void
    {
        $service = section_service(['provider' => 'custom', 'serviceName' => 'Instagram Feed!', 'company' => 'Meta Platforms', 'category' => 'statistics']);

        self::assertSame(['id' => 'custom-instagram-feed', 'label' => 'Instagram Feed!', 'company' => 'Meta Platforms', 'category' => 'statistics'], $service);
    }

    public function testUnknownCategoryFallsBackToMarketing(): void
    {
        self::assertSame('marketing', section_service(['provider' => 'custom', 'serviceName' => 'X', 'category' => 'everything'])['category']);
    }

    public function testCustomServiceWithoutNameIsNotRenderable(): void
    {
        self::assertNull(section_service(['provider' => 'custom', 'serviceName' => '  ']));
        self::assertNull(section_service(['provider' => 'no-such-provider']));
    }

    public function testCompanyDefaultsToTheServiceName(): void
    {
        self::assertSame('Calendly', section_service(['provider' => 'custom', 'serviceName' => 'Calendly'])['company']);
    }
}
