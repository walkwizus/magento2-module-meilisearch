<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchSearchAdapter\Test\Unit\Plugin\Service;

use Meilisearch\Contracts\Task;
use PHPUnit\Framework\TestCase;
use Walkwizus\MeilisearchBase\Service\SettingsManager;
use Walkwizus\MeilisearchSearchAdapter\Plugin\Service\AdjustIndexSettings;

class AdjustIndexSettingsTest extends TestCase
{
    public function testFullReindexKeepsTheRankingRulesOfTheLiveIndex(): void
    {
        $task = Task::fromArray(['taskUid' => 1, 'status' => 'enqueued', 'type' => 'settingsUpdate', 'enqueuedAt' => '2026-01-01T00:00:00Z']);
        $settingsManager = $this->createMock(SettingsManager::class);
        $settingsManager->expects(self::once())
            ->method('getSettings')
            ->with('mageos_catalog_product_1')
            ->willReturn(['rankingRules' => ['words', 'exactness', 'typo', 'sort']]);
        $settingsManager->expects(self::once())
            ->method('updateSettings')
            ->with('mageos_catalog_product_1_tmp', self::callback(
                fn (array $settings) => $settings['rankingRules'] === ['sort', 'words', 'exactness', 'typo']
            ))
            ->willReturn($task);

        (new AdjustIndexSettings())->afterUpdateFilterableAttributes($settingsManager, $task, 'mageos_catalog_product_1_tmp');
    }
}
