<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchCatalog\Model\Price;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class StoreCurrencyResolver
{
    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager
    ) { }

    /**
     * @param int $storeId
     * @return string[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCurrencyCodes(int $storeId): array
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore($storeId);
        $baseCurrency = $store->getBaseCurrency();
        $availableCodes = array_values(array_filter($store->getAvailableCurrencyCodes(true)));
        $codes = array_values(array_filter(
            $availableCodes,
            static fn (string $code): bool => (bool) $baseCurrency->getRate($code)
        ));

        if ($codes === [] || count($codes) < count($availableCodes)) {
            $codes[] = $store->getBaseCurrencyCode();
        }

        return array_values(array_unique($codes));
    }
}
