<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Meilisearch\Contracts\BatchProgressStep;
use Meilisearch\Contracts\Task;
use Meilisearch\Contracts\TasksQuery;
use Meilisearch\Contracts\TaskStatus;
use Meilisearch\Exceptions\ApiException;
use Psr\Log\LoggerInterface;
use Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder\Link as LinkResource;
use Walkwizus\MeilisearchBase\SearchAdapter\ConnectionManager;

class EmbeddingStatus
{
    public const STATE_NONE = 'none';
    public const STATE_PROCESSING = 'processing';
    public const STATE_READY = 'ready';
    public const STATE_INCOMPLETE = 'incomplete';
    public const STATE_FAILED = 'failed';
    public const STATE_UNKNOWN = 'unknown';

    private const CACHE_KEY_PREFIX = 'meilisearch_ai_embedding_status_';
    private const CACHE_LIFETIME = 15;
    private const TASK_TYPES = ['settingsUpdate', 'documentAdditionOrUpdate'];
    private const DOCUMENT_STEP = 'document';

    /**
     * @var array
     */
    private array $statuses = [];

    /**
     * @param ConnectionManager $connectionManager
     * @param LinkResource $linkResource
     * @param CacheInterface $cache
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ConnectionManager $connectionManager,
        private readonly LinkResource $linkResource,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param string $indexUid
     * @return array
     * @throws LocalizedException
     */
    public function get(string $indexUid): array
    {
        if (isset($this->statuses[$indexUid])) {
            return $this->statuses[$indexUid];
        }

        if ($this->linkResource->getEmbedderIdsByUid($indexUid) === []) {
            return $this->statuses[$indexUid] = $this->status(self::STATE_NONE);
        }

        $cacheKey = self::CACHE_KEY_PREFIX . $indexUid;
        $cached = $this->cache->load($cacheKey);

        if ($cached !== false) {
            return $this->statuses[$indexUid] = (array) json_decode((string) $cached, true);
        }

        try {
            $status = $this->fetch($indexUid);
        } catch (\Exception $e) {
            $this->logger->warning('Meilisearch: unable to read the embedding status of index ' . $indexUid, ['exception' => $e]);
            $status = $this->status(self::STATE_UNKNOWN, error: $e->getMessage());
        }

        $this->cache->save((string) json_encode($status), $cacheKey, [EmbedderAvailability::CACHE_TAG], self::CACHE_LIFETIME);

        return $this->statuses[$indexUid] = $status;
    }

    /**
     * @param array $status
     * @return Phrase
     */
    public function getLabel(array $status): Phrase
    {
        return match ($status['state']) {
            self::STATE_PROCESSING => match (true) {
                $status['documents'] > 0 => __(
                    'In progress: %1 / %2 documents (%3%)',
                    number_format((int) $status['embedded']),
                    number_format((int) $status['documents']),
                    number_format((float) $status['progress'])
                ),
                $status['progress'] !== null => __('In progress (%1%)', number_format((float) $status['progress'])),
                default => __('In progress')
            },
            self::STATE_READY => __('Ready (%1 / %2)', number_format((int) $status['embedded']), number_format((int) $status['documents'])),
            self::STATE_INCOMPLETE => __('Incomplete (%1 / %2)', number_format((int) $status['embedded']), number_format((int) $status['documents'])),
            self::STATE_FAILED => __('Failed: %1', $status['error']),
            self::STATE_UNKNOWN => __('Unknown: %1', $status['error']),
            default => __('No embedder')
        };
    }

    /**
     * @param string $indexUid
     * @return array
     * @throws ApiException
     */
    private function fetch(string $indexUid): array
    {
        $index = $this->connectionManager->getConnection()->index($indexUid);
        $activeTasks = $index->getTasks(
            (new TasksQuery())
                ->setStatuses([TaskStatus::Enqueued->value, TaskStatus::Processing->value])
                ->setTypes(self::TASK_TYPES)
                ->setLimit(20)
        )->getResults();

        if ($activeTasks !== []) {
            [$processed, $total, $progress] = $this->getProgress($activeTasks);

            return $this->status(self::STATE_PROCESSING, $total, $processed, $progress);
        }

        $lastTask = $index->getTasks((new TasksQuery())->setTypes(self::TASK_TYPES)->setLimit(1))->getResults()[0] ?? null;

        if ($lastTask instanceof Task && $lastTask->getStatus() === TaskStatus::Failed) {
            return $this->status(self::STATE_FAILED, error: (string) $lastTask->getError()?->message);
        }

        $stats = $index->stats();
        $documents = $stats->getNumberOfDocuments();
        $embedded = $stats->getNumberOfEmbeddedDocuments();

        return $this->status(
            $embedded >= $documents ? self::STATE_READY : self::STATE_INCOMPLETE,
            $documents,
            $embedded
        );
    }

    /**
     * @param Task[] $tasks
     * @return array
     */
    private function getProgress(array $tasks): array
    {
        foreach ($tasks as $task) {
            if ($task->getStatus() !== TaskStatus::Processing || $task->getBatchUid() === null) {
                continue;
            }

            try {
                $progress = $this->connectionManager->getConnection()->getBatch($task->getBatchUid())->getProgress();
            } catch (ApiException $e) {
                return [0, 0, null];
            }

            $steps = $progress?->getSteps() ?? [];
            $lastStep = end($steps);

            if ($lastStep instanceof BatchProgressStep && $lastStep->getCurrentStep() === self::DOCUMENT_STEP && $lastStep->getTotal() > 0) {
                return [
                    $lastStep->getFinished(),
                    $lastStep->getTotal(),
                    $lastStep->getFinished() / $lastStep->getTotal() * 100
                ];
            }

            return [0, 0, $progress?->getPercentage()];
        }

        return [0, 0, null];
    }

    /**
     * @param string $state
     * @param int $documents
     * @param int $embedded
     * @param float|null $progress
     * @param string|null $error
     * @return array
     */
    private function status(
        string $state,
        int $documents = 0,
        int $embedded = 0,
        ?float $progress = null,
        ?string $error = null
    ): array {
        return [
            'state' => $state,
            'documents' => $documents,
            'embedded' => $embedded,
            'progress' => $progress,
            'error' => $error
        ];
    }
}
