<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model;

use Walkwizus\MeilisearchMerchandising\Service\QueryBuilderService;

class CategoryFilterResolver
{
    /**
     * @param QueryBuilderService $queryBuilderService
     */
    public function __construct(
        private readonly QueryBuilderService $queryBuilderService
    ) { }

    /**
     * @param int $storeId
     * @param int $categoryId
     * @param array|null $rules
     * @return string
     */
    public function resolve(int $storeId, int $categoryId, ?array $rules): string
    {
        if ($rules !== null && ($rules['valid'] ?? true) && !empty($rules['rules'])) {
            $filter = $this->queryBuilderService->convertRulesToMeilisearchQuery($rules, $storeId);

            if ($filter !== '') {
                return $filter;
            }
        }

        return 'category_ids = ' . $categoryId;
    }
}
