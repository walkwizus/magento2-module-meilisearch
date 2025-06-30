<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Block;

use Magento\Framework\View\Element\Template;
use Walkwizus\MeilisearchFrontend\ViewModel\JsConfig;

class Config extends Template
{
    const COMPONENT_NAME = 'meilisearch-frontend-config';

    /**
     * @return string
     */
    public function getJsLayout(): string
    {
        /** @var JsConfig $viewModel */
        $viewModel = $this->getViewModel();

        foreach ($viewModel->get(self::COMPONENT_NAME) as $key => $value) {
            $this->jsLayout['components'][self::COMPONENT_NAME]['config']['meilisearchConfig'][$key] = $value;
        }

        return parent::getJsLayout();
    }
}
