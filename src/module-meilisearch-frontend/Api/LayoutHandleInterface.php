<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Api;

use Magento\Framework\View\LayoutInterface;

interface LayoutHandleInterface
{
    /**
     * @param LayoutInterface $layout
     * @param string $fullActionName
     * @return bool
     */
    public function isApplicable(LayoutInterface $layout, string $fullActionName): bool;
}
