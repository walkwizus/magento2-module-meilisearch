<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchIndices\Ui\DataProvider\Indices\Form\Modifier;

use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Walkwizus\MeilisearchBase\Service\SettingsManager;

class DisableOnAttributes implements ModifierInterface
{
    /**
     * @param RequestInterface $request
     * @param SettingsManager $settingsManager
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly SettingsManager $settingsManager
    ) { }

    /**
     * @param array $data
     * @return array
     */
    public function modifyData(array $data): array
    {
        return $data;
    }

    /**
     * @param array $meta
     * @return array
     * @throws \Exception
     */
    public function modifyMeta(array $meta): array
    {
        $indexName = $this->request->getParam('id');
        $settings = $this->settingsManager->getSettings($indexName);

        $options = [];
        foreach ($settings['searchableAttributes'] as $searchableAttribute) {
            $options[] = [
                'label' => $searchableAttribute,
                'value' => $searchableAttribute,
            ];
        }

        $meta['meilisearch_indices_fieldset_typo_tolerance'] = [
            'children' => [
                'disableOnAttributes' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'options' => $options,
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $meta;
    }
}
