<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchIndices\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\App\RequestInterface;
use Walkwizus\MeilisearchBase\Service\SettingsManager;

class SearchableAttributes implements OptionSourceInterface
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
     * @return array
     * @throws \Exception
     */
    public function toOptionArray()
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

        return $options;
    }
}
