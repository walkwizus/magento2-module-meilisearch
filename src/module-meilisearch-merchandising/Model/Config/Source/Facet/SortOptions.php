<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model\Config\Source\Facet;

use Magento\Framework\Data\OptionSourceInterface;

class SortOptions implements OptionSourceInterface
{
    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'label' => __('Magento Native'),
                'value' => 'magento'
            ],
            [
                'label' => __('Alpha'),
                'value' => 'alpha'
            ],
            [
                'label' => __('Product Count'),
                'value' => 'count'
            ],
        ];
    }
}
