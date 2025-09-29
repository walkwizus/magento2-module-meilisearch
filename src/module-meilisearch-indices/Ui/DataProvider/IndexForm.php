<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchIndices\Ui\DataProvider;

use Magento\Ui\DataProvider\ModifierPoolDataProvider;
use Walkwizus\MeilisearchBase\Service\SettingsManager;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Magento\Framework\Api\Filter;

class IndexForm extends ModifierPoolDataProvider
{

    /**
     * @var string|null
     */
    private ?string $requestedId = null;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param SettingsManager $settingsManager
     * @param array $meta
     * @param array $data
     * @param PoolInterface|null $pool
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        private readonly SettingsManager $settingsManager,
        array $meta = [],
        array $data = [],
        ?PoolInterface $pool = null
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data, $pool);
    }

    /**
     * @return array[]
     * @throws \Exception
     */
    public function getData(): array
    {
        if (!$this->requestedId) {
            return [];
        }

        $settings = $this->settingsManager->getSettings($this->requestedId);

        $rankingRules = [];
        foreach ($settings['rankingRules'] as $index => $rule) {
            $rankingRules[] = [
                'rule' => $rule,
                'position' => (int)$index
            ];
        }

        $synonyms = [];
        foreach ($settings['synonyms'] as $word => $synonym) {
            $synonyms[] = [
                'word' => $word,
                'synonyms' => implode(',', $synonym)
            ];
        }

        return [
            $this->requestedId => [
                'id' => $this->requestedId,
                'rankingRules' => $rankingRules,
                'stopWords' => implode(',', $settings['stopWords']),
                'synonyms' => $synonyms,
                'enableTypoTolerance' => $settings['typoTolerance']['enabled'],
                'oneTypo' => $settings['typoTolerance']['minWordSizeForTypos']['oneTypo'],
                'twoTypos' => $settings['typoTolerance']['minWordSizeForTypos']['twoTypos'],
                'disableOnNumbers' => $settings['typoTolerance']['disableOnNumbers'],
                'disableOnAttributes' => array_values($settings['typoTolerance']['disableOnAttributes'])
            ]
        ];
    }

    /**
     * @param Filter $filter
     * @return void
     */
    public function addFilter(Filter $filter): void
    {
        if ($filter->getField() === $this->getRequestFieldName()) {
            $this->requestedId = $filter->getValue();
        }
    }
}
