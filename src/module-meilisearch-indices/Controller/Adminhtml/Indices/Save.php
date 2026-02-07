<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchIndices\Controller\Adminhtml\Indices;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action\Context;
use Walkwizus\MeilisearchBase\Service\SettingsManager;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Redirect;

class Save extends Action implements HttpPostActionInterface
{
    /**
     * @param Context $context
     * @param SettingsManager $settingsManager
     */
    public function __construct(
        Context $context,
        private readonly SettingsManager $settingsManager
    ) {
        parent::__construct($context);
    }

    /**
     * @return Redirect
     * @throws \Exception
     */
    public function execute(): Redirect
    {
        $post = $this->getRequest()->getPost();
        $indexName = $post['id'];

        $this->settingsManager->updateRankingRules($indexName, $this->getRankingRules());
        $this->settingsManager->updateStopWords($indexName, $this->getStopWords());
        $this->settingsManager->updateSynonyms($indexName, $this->getSynonyms());
        $this->settingsManager->updateTypoTolerance($indexName, $this->getTypoTolerance());

        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $redirect->setPath('*/*/index');
    }

    /**
     * @return array
     */
    private function getRankingRules(): array
    {
        $post = $this->getRequest()->getPost();

        $rankingRulesData = $post['rankingRules'] ?? [];
        usort($rankingRulesData, static function ($a, $b) {
            return ($a['position'] ?? 0) <=> ($b['position'] ?? 0);
        });

        return array_column($rankingRulesData, 'rule');
    }

    /**
     * @return array
     */
    public function getStopWords(): array
    {
        $post = $this->getRequest()->getPost();
        return array_filter(array_map('trim', explode(',', (string)$post['stopWords'])));
    }

    /**
     * @return array
     */
    private function getSynonyms(): array
    {
        $post = $this->getRequest()->getPost();

        $synonyms = [];
        foreach ($post['synonyms'] ?? [] as $entry) {
            $word = trim((string)($entry['word'] ?? ''));
            $values = array_filter(array_map('trim', explode(',', (string)($entry['synonyms'] ?? ''))));

            if ($word !== '' && !empty($values)) {
                $synonyms[$word] = $values;
            }
        }

        return $synonyms;
    }

    /**
     * @return array
     */
    private function getTypoTolerance(): array
    {
        $post = $this->getRequest()->getPost();

        $disableOnWords = array_filter(array_map('trim', explode(',', (string)($post['disableOnWords'] ?? ''))));
        $disableOnAttributes = (isset($post['disableOnAttributes']) && is_array($post['disableOnAttributes']))
            ? array_values($post['disableOnAttributes'])
            : [];

        return [
            'enabled' => $post['enableTypoTolerance'] === 'true',
            'minWordSizeForTypos' => [
                'oneTypo' => (int)$post['oneTypo'],
                'twoTypos' => (int)$post['twoTypos'],
            ],
            'disableOnWords' => $disableOnWords,
            'disableOnAttributes' => $disableOnAttributes,
            'disableOnNumbers' => $post['disableOnNumbers'] === 'true'
        ];
    }
}
