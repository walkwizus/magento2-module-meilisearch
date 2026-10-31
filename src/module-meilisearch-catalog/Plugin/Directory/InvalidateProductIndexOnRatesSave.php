<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchCatalog\Plugin\Directory;

use Magento\CatalogSearch\Model\Indexer\Fulltext;
use Magento\Directory\Model\Currency;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\IndexerRegistry;

class InvalidateProductIndexOnRatesSave
{
    /**
     * @var array
     */
    private array $previousRates = [];

    /**
     * @param IndexerRegistry $indexerRegistry
     * @param ResourceConnection $resource
     */
    public function __construct(
        private readonly IndexerRegistry $indexerRegistry,
        private readonly ResourceConnection $resource
    ) { }

    /**
     * @param Currency $subject
     * @param array $rates
     * @return null
     */
    public function beforeSaveRates(Currency $subject, $rates)
    {
        $this->previousRates = is_array($rates) ? $this->fetchRates($rates) : [];

        return null;
    }

    /**
     * @param Currency $subject
     * @param Currency $result
     * @param array $rates
     * @return Currency
     */
    public function afterSaveRates(Currency $subject, Currency $result, $rates): Currency
    {
        if (is_array($rates) && $this->fetchRates($rates) !== $this->previousRates) {
            $this->indexerRegistry->get(Fulltext::INDEXER_ID)->invalidate();
        }

        $this->previousRates = [];

        return $result;
    }

    /**
     * @param array $rates
     * @return array
     */
    private function fetchRates(array $rates): array
    {
        $connection = $this->resource->getConnection();
        $conditions = [];

        foreach ($rates as $currencyFrom => $currencyRates) {
            foreach (array_keys((array) $currencyRates) as $currencyTo) {
                $conditions[] = $connection->quoteInto('currency_from = ?', (string) $currencyFrom)
                    . $connection->quoteInto(' AND currency_to = ?', (string) $currencyTo);
            }
        }

        if ($conditions === []) {
            return [];
        }

        $select = $connection->select()
            ->from(
                $this->resource->getTableName('directory_currency_rate'),
                ['pair' => new \Zend_Db_Expr("CONCAT(currency_from, '|', currency_to)"), 'rate']
            )
            ->where('(' . implode(') OR (', $conditions) . ')')
            ->order('pair ASC');

        return $connection->fetchPairs($select);
    }
}
