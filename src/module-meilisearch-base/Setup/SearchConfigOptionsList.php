<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Setup;

use Magento\Framework\Setup\Option\TextConfigOption;
use Magento\Setup\Model\SearchConfigOptionsList as BaseSearchConfigOptionsList;

class SearchConfigOptionsList extends BaseSearchConfigOptionsList
{
    const ENGINE_CODE = 'meilisearch';

    const INPUT_KEY_MEILISEARCH_ADDRESS = 'meilisearch-address';
    const INPUT_KEY_MEILISEARCH_MASTER_KEY = 'meilisearch-master-key';
    const INPUT_KEY_MEILISEARCH_API_KEY = 'meilisearch-api-key';
    const INPUT_KEY_MEILISEARCH_CLIENT_ADDRESS = 'meilisearch-client-address';
    const INPUT_KEY_MEILISEARCH_CLIENT_API_KEY = 'meilisearch-client-api-key';
    const INPUT_KEY_MEILISEARCH_INDEXES_PREFIX = 'meilisearch-indexes-prefix';

    /**
     * @return array
     */
    public function getOptionsList(): array
    {
        return array_merge(parent::getOptionsList(), [
            new TextConfigOption(
                self::INPUT_KEY_MEILISEARCH_ADDRESS,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                '',
                'Meilisearch server address used by Magento (e.g. http://localhost:7700).'
            ),
            new TextConfigOption(
                self::INPUT_KEY_MEILISEARCH_MASTER_KEY,
                TextConfigOption::FRONTEND_WIZARD_PASSWORD,
                '',
                'Meilisearch master key. Used to retrieve the default API keys when they are not provided.'
            ),
            new TextConfigOption(
                self::INPUT_KEY_MEILISEARCH_API_KEY,
                TextConfigOption::FRONTEND_WIZARD_PASSWORD,
                '',
                'Meilisearch admin API key (server side).'
            ),
            new TextConfigOption(
                self::INPUT_KEY_MEILISEARCH_CLIENT_ADDRESS,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                '',
                'Meilisearch server address used by the browser. Defaults to --meilisearch-address.'
            ),
            new TextConfigOption(
                self::INPUT_KEY_MEILISEARCH_CLIENT_API_KEY,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                '',
                'Meilisearch search-only API key (client side, exposed publicly).'
            ),
            new TextConfigOption(
                self::INPUT_KEY_MEILISEARCH_INDEXES_PREFIX,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                '',
                'Meilisearch indexes name prefix.'
            ),
        ]);
    }

    /**
     * @return array
     */
    public function getAvailableSearchEngineList(): array
    {
        return array_merge(parent::getAvailableSearchEngineList(), [
            self::ENGINE_CODE => 'Meilisearch',
        ]);
    }
}
