<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchSearchAdapter\Model\Config\Source;

class FallbackStrategy extends MatchingStrategy
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [['value' => '', 'label' => __('None')], ...parent::toOptionArray()];
    }
}
