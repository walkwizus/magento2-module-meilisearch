<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Block;

use Magento\Framework\View\Element\Template;
use Walkwizus\MeilisearchFrontend\ViewModel\JsConfig;

class Config extends Template
{
    /**
     * @return string
     */
    public function getJsLayout(): string
    {
        /** @var JsConfig $viewModel */
        $viewModel = $this->getViewModel();

        foreach ($viewModel->get() as $key => $value) {
            $this->jsLayout[$key] = $value;
        }

        return parent::getJsLayout();
    }
}
