<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Api;

interface AttributeRuleProviderInterface
{
    /**
     * @return array
     */
    public function getAttributes(): array;
}
