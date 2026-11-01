<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchSearchAdapter\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class MatchingStrategy implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'all', 'label' => __('All words')],
            ['value' => 'frequency', 'label' => __('Frequency (drop the most common words first)')],
            ['value' => 'last', 'label' => __('Last (drop words from the end first)')],
        ];
    }
}
