<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Service;

use Magento\Framework\Phrase\Renderer\TranslateFactory;
use Magento\Framework\TranslateInterfaceFactory;
use Magento\Framework\TranslateInterface;
use Magento\Framework\Phrase;

class Translation
{
    /**
     * @var array
     */
    private array $translatorPool = [];

    /**
     * @param TranslateFactory $rendererFactory
     * @param TranslateInterfaceFactory $translateFactory
     */
    public function __construct(
        private readonly TranslateFactory $rendererFactory,
        private readonly TranslateInterfaceFactory $translateFactory
    ) { }

    /**
     * @param string $string
     * @param string $locale
     * @return string
     */
    public function translateByLangCode(string $string, string $locale): string
    {
        $translator = $this->getTranslator($locale);
        $orgRenderer = Phrase::getRenderer();

        $renderer = $this->rendererFactory->create(['translator' => $translator]);
        Phrase::setRenderer($renderer);

        $phrase = new Phrase($string);
        $translation = (string)$phrase;

        Phrase::setRenderer($orgRenderer);

        return $translation;
    }

    /**
     * @param string $locale
     * @return TranslateInterface|mixed
     */
    private function getTranslator(string $locale): mixed
    {
        if (!isset($this->translatorPool[$locale])) {
            $translator = $this->translateFactory->create();
            $translator->setLocale($locale);
            $translator->loadData();
            $this->translatorPool[$locale] = $translator;
        }

        return $this->translatorPool[$locale];
    }
}
