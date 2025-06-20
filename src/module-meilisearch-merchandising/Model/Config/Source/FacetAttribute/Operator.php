<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model\Config\Source\FacetAttribute;

use Magento\Framework\Data\OptionSourceInterface;

class Operator implements OptionSourceInterface
{
    const OPERATOR_OR = 'or';

    const OPERATOR_AND = 'and';

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'label' => 'or',
                'value' => 'or',
            ],
            [
                'label' => 'and',
                'value' => 'and',
            ],
        ];
    }
}
