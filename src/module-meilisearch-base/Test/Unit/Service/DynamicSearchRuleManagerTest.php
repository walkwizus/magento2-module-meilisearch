<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Test\Unit\Service;

use GuzzleHttp\Client as HttpClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Walkwizus\MeilisearchBase\Model\Config\ServerSettings;
use Walkwizus\MeilisearchBase\SearchAdapter\ConnectionManager;
use Walkwizus\MeilisearchBase\Service\DynamicSearchRuleManager;

class DynamicSearchRuleManagerTest extends TestCase
{
    /**
     * @return array
     */
    public static function versionProvider(): array
    {
        return [
            ['1.53.2', false],
            ['1.54.0', true],
            ['1.55.0', true],
            ['1.54.0-rc.1', true],
            ['2.0.0', true],
        ];
    }

    #[DataProvider('versionProvider')]
    public function testIsSupportedVersion(string $version, bool $expected): void
    {
        $manager = new DynamicSearchRuleManager(
            $this->createMock(ConnectionManager::class),
            $this->createMock(ServerSettings::class),
            $this->createMock(HttpClient::class)
        );

        $this->assertSame($expected, $manager->isSupportedVersion($version));
    }
}
