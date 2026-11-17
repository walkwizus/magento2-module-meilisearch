<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Test\Unit\Service;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Meilisearch\Client;
use Meilisearch\Contracts\Task;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Walkwizus\MeilisearchBase\SearchAdapter\ConnectionManager;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Walkwizus\MeilisearchBase\Service\DocumentsManager;
use Walkwizus\MeilisearchMerchandising\Model\CategoryProduct\SortFields;
use Walkwizus\MeilisearchMerchandising\Service\SaveProductPosition;

class SaveProductPositionTest extends TestCase
{
    private const INDEX = 'mageos_catalog_product_2';

    private array $updates = [];

    private array $replacements = [];

    public function testPositionedProductsArePartiallyUpdated(): void
    {
        $this->execute([1 => ['merch_rank_category_26' => 4]], [1 => 5], [1], [['product_id' => 1, 'position' => 1, 'is_buried' => false]]);

        $this->assertSame([['id' => 1, 'merch_rank_category_26' => 1, 'position_category_26' => 5]], $this->updates);
        $this->assertSame([], $this->replacements);
    }

    public function testBuriedProductsLoseTheirRankAndKeepTheRestOfTheDocument(): void
    {
        $this->execute([2 => ['merch_rank_category_26' => 2, 'position_category_26' => 8]], [2 => 8], [2], [['product_id' => 2, 'position' => 3, 'is_buried' => true]]);

        $this->assertSame([], $this->updates);
        $this->assertSame([['id' => 2, 'name' => 'Product 2', 'position_category_26' => SortFields::BURIED_POSITION_OFFSET + 3]], $this->replacements);
    }

    public function testResetProductsGoBackToTheirNativePosition(): void
    {
        $this->execute([3 => ['merch_rank_category_26' => 1, 'position_category_26' => SortFields::BURIED_POSITION_OFFSET + 1]], [3 => 12], [3], []);

        $this->assertSame([['id' => 3, 'name' => 'Product 3', 'position_category_26' => 12]], $this->replacements);
    }

    public function testProductsMissingFromTheIndexAreSkipped(): void
    {
        $this->execute([], [], [4], [['product_id' => 4, 'position' => 1, 'is_buried' => false]]);

        $this->assertSame([], $this->updates);
        $this->assertSame([], $this->replacements);
    }

    /**
     * @param array $indexedFields
     * @param array $nativePositions
     * @param array $productIds
     * @param array $rows
     * @return void
     * @throws \Exception
     */
    private function execute(array $indexedFields, array $nativePositions, array $productIds, array $rows): void
    {
        $documents = [];
        foreach ($indexedFields as $id => $fields) {
            $documents[$id] = ['id' => $id, 'name' => 'Product ' . $id] + $fields;
        }

        $resolver = $this->createMock(SearchIndexNameResolver::class);
        $resolver->method('getIndexName')->willReturn(self::INDEX);

        $documentsManager = $this->createMock(DocumentsManager::class);
        $documentsManager->method('updateDocuments')->willReturnCallback(function (string $index, array $docs): Task {
            $this->updates = $docs;
            return $this->createTask();
        });
        $documentsManager->method('addDocuments')->willReturnCallback(function (string $index, array $docs): Task {
            $this->replacements = $docs;
            return $this->createTask();
        });

        $connectionManager = $this->createMock(ConnectionManager::class);
        $connectionManager->method('getConnection')->willReturn($this->createClient($documents));

        $select = $this->createMock(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchPairs')->willReturn($nativePositions);
        $resourceConnection = $this->createMock(ResourceConnection::class);
        $resourceConnection->method('getConnection')->willReturn($connection);

        $tableMaintainer = $this->createMock(TableMaintainer::class);
        $tableMaintainer->method('getMainTable')->willReturn('catalog_category_product_index_store2');

        (new SaveProductPosition($resolver, $documentsManager, $connectionManager, $resourceConnection, $tableMaintainer, new SortFields()))
            ->execute(2, 26, $productIds, $rows);
    }

    /**
     * @return Task
     */
    private function createTask(): Task
    {
        return Task::fromArray(['taskUid' => 1, 'indexUid' => self::INDEX, 'status' => 'enqueued', 'type' => 'documentAdditionOrUpdate', 'enqueuedAt' => '2026-11-01T00:00:00Z']);
    }

    /**
     * @param array $documents
     * @return Client
     */
    private function createClient(array $documents): Client
    {
        $httpClient = new class ($documents) implements ClientInterface {
            /**
             * @param array $documents
             */
            public function __construct(private readonly array $documents)
            {
            }

            /**
             * @param RequestInterface $request
             * @return ResponseInterface
             */
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                parse_str($request->getUri()->getQuery(), $query);
                $fields = isset($query['fields']) ? explode(',', $query['fields']) : null;
                $results = [];

                foreach (explode(',', $query['ids'] ?? '') as $id) {
                    if (isset($this->documents[(int) $id])) {
                        $document = $this->documents[(int) $id];
                        $results[] = $fields !== null ? array_intersect_key($document, array_flip($fields)) : $document;
                    }
                }

                return new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
                    'results' => $results,
                    'offset' => 0,
                    'limit' => count($results),
                    'total' => count($results),
                ]));
            }
        };

        $factory = new HttpFactory();

        return new Client('http://meilisearch.test', 'key', $httpClient, $factory, [], $factory);
    }
}
