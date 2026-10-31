<?php

declare(strict_types=1);

if (PHP_SAPI == 'cli') {
    \Magento\Framework\Console\CommandLocator::register(\Walkwizus\MeilisearchBase\Console\SetupCommandList::class);
}
