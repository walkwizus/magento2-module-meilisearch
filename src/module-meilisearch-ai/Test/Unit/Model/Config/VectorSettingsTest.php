<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Test\Unit\Model\Config;

use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use PHPUnit\Framework\TestCase;
use Walkwizus\MeilisearchAi\Model\Config\VectorSettings;

class VectorSettingsTest extends TestCase
{
    public function testGetVectorSettingsReturnsExpectedData(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $writer = $this->createMock(WriterInterface::class);
        $reinitableConfig = $this->createMock(ReinitableConfigInterface::class);

        $indexUid = 'products_1';
        $prefix = VectorSettings::XML_PATH_MEILISEARCH_AI_INDICES_SETTINGS_PREFIX . '/' . $indexUid . '_';

        $scopeConfig->method('getValue')->willReturnMap([
            [$prefix . VectorSettings::IS_VECTOR_ENABLED, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, true],
            [$prefix . VectorSettings::EMBEDDER_ID, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, 7],
            [$prefix . VectorSettings::SEMANTIC_RATIO, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, 0.6],
            [$prefix . VectorSettings::RANKING_SCORE_THRESHOLD, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, 0.2],
        ]);

        $vectorSettings = new VectorSettings($scopeConfig, $writer, $reinitableConfig);

        self::assertSame(
            [
                'is_vector_enabled' => true,
                'embedder_id' => 7,
                'semantic_ratio' => 0.6,
                'ranking_score_threshold' => 0.2,
            ],
            $vectorSettings->getVectorSettings($indexUid)
        );
    }

    public function testSetVectorSettingsWritesAllValuesAndReinitializesConfig(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $writer = $this->createMock(WriterInterface::class);
        $reinitableConfig = $this->createMock(ReinitableConfigInterface::class);

        $indexUid = 'products_2';
        $prefix = VectorSettings::XML_PATH_MEILISEARCH_AI_INDICES_SETTINGS_PREFIX . '/' . $indexUid . '_';

        $savedValues = [];
        $writer->expects(self::exactly(4))
            ->method('save')
            ->willReturnCallback(static function ($path, $value) use (&$savedValues): void {
                $savedValues[(string) $path] = $value;
            });

        $reinitableConfig->expects(self::once())
            ->method('reinit')
            ->willReturnSelf();

        $scopeConfig->method('getValue')->willReturnMap([
            [$prefix . VectorSettings::IS_VECTOR_ENABLED, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, true],
            [$prefix . VectorSettings::EMBEDDER_ID, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, 11],
            [$prefix . VectorSettings::SEMANTIC_RATIO, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, 0.75],
            [$prefix . VectorSettings::RANKING_SCORE_THRESHOLD, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, 0.3],
        ]);

        $vectorSettings = new VectorSettings($scopeConfig, $writer, $reinitableConfig);

        $result = $vectorSettings->setVectorSettings($indexUid, true, 11, 0.75, 0.3);

        self::assertSame(
            [
                $prefix . VectorSettings::IS_VECTOR_ENABLED => true,
                $prefix . VectorSettings::EMBEDDER_ID => 11,
                $prefix . VectorSettings::SEMANTIC_RATIO => 0.75,
                $prefix . VectorSettings::RANKING_SCORE_THRESHOLD => 0.3,
            ],
            $savedValues
        );
        self::assertSame(
            [
                'is_vector_enabled' => true,
                'embedder_id' => 11,
                'semantic_ratio' => 0.75,
                'ranking_score_threshold' => 0.3,
            ],
            $result
        );
    }
}
