<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Walkwizus\MeilisearchFrontend\Model\ConfigProvider;
use Magento\Framework\Serialize\Serializer\Json;

class JsConfig implements ArgumentInterface
{
    /**
     * @param ConfigProvider $configProvider
     * @param Json $json
     */
    public function __construct(
        private readonly ConfigProvider $configProvider,
        private readonly Json $json
    ) { }

    /**
     * @return string
     */
    public function getJsConfig(): string
    {
        return $this->json->serialize($this->configProvider->get());
    }
}
