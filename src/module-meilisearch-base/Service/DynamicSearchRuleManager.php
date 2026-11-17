<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Service;

use GuzzleHttp\Client as HttpClient;
use Meilisearch\Contracts\DynamicSearchRule;
use Meilisearch\Contracts\DynamicSearchRulesQuery;
use Meilisearch\Contracts\UpdateDynamicSearchRuleQuery;
use Meilisearch\Exceptions\ApiException;
use Walkwizus\MeilisearchBase\Model\Config\ServerSettings;
use Walkwizus\MeilisearchBase\SearchAdapter\ConnectionManager;

class DynamicSearchRuleManager
{
    public const MIN_VERSION = '1.54.0';
    public const EXPERIMENTAL_FEATURE = 'dynamicSearchRules';

    public const STATUS_ENABLED = 'enabled';
    public const STATUS_DISABLED = 'disabled';
    public const STATUS_UNSUPPORTED = 'unsupported';
    public const STATUS_UNAVAILABLE = 'unavailable';

    private const PAGE_SIZE = 100;

    /**
     * @var string|null
     */
    private ?string $status = null;

    /**
     * @param ConnectionManager $connectionManager
     * @param ServerSettings $serverSettings
     * @param HttpClient $httpClient
     */
    public function __construct(
        private readonly ConnectionManager $connectionManager,
        private readonly ServerSettings $serverSettings,
        private readonly HttpClient $httpClient
    ) {
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        if ($this->status !== null) {
            return $this->status;
        }

        try {
            $version = $this->connectionManager->getConnection(true)->version()->getPkgVersion();

            if (!$this->isSupportedVersion($version)) {
                return $this->status = self::STATUS_UNSUPPORTED;
            }

            $features = $this->requestExperimentalFeatures('GET');
        } catch (\Exception $e) {
            return $this->status = self::STATUS_UNAVAILABLE;
        }

        return $this->status = !empty($features[self::EXPERIMENTAL_FEATURE]) ? self::STATUS_ENABLED : self::STATUS_DISABLED;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->getStatus() === self::STATUS_ENABLED;
    }

    /**
     * @param string $version
     * @return bool
     */
    public function isSupportedVersion(string $version): bool
    {
        return version_compare(strtok($version, '-'), self::MIN_VERSION, '>=');
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function enable(): void
    {
        $this->status = null;

        if ($this->getStatus() === self::STATUS_UNSUPPORTED) {
            throw new \RuntimeException(sprintf('Dynamic search rules require Meilisearch %s or later.', self::MIN_VERSION));
        }

        $this->requestExperimentalFeatures('PATCH', [self::EXPERIMENTAL_FEATURE => true]);
        $this->status = null;
    }

    /**
     * @param string $uid
     * @param array $conditions
     * @param array $actions
     * @param string|null $description
     * @param int|null $precedence
     * @return void
     * @throws \Exception
     */
    public function saveRule(string $uid, array $conditions, array $actions, ?string $description = null, ?int $precedence = null): void
    {
        $this->connectionManager->getConnection(true)->updateDynamicSearchRule(
            (new UpdateDynamicSearchRuleQuery($uid))
                ->setDescription($description)
                ->setPrecedence($precedence)
                ->setActive(true)
                ->setConditions($conditions)
                ->setActions($actions)
        );
    }

    /**
     * @param string $uid
     * @return void
     * @throws \Exception
     */
    public function deleteRule(string $uid): void
    {
        try {
            $this->connectionManager->getConnection(true)->deleteDynamicSearchRule($uid);
        } catch (ApiException $e) {
            if ($e->httpStatus !== 404) {
                throw $e;
            }
        }
    }

    /**
     * @param string $prefix
     * @return string[]
     * @throws \Exception
     */
    public function getRuleUids(string $prefix = ''): array
    {
        $client = $this->connectionManager->getConnection(true);
        $uids = [];
        $offset = 0;

        do {
            $results = $client->getDynamicSearchRules((new DynamicSearchRulesQuery())->setOffset($offset)->setLimit(self::PAGE_SIZE));

            /** @var DynamicSearchRule $rule */
            foreach ($results->getResults() as $rule) {
                if ($prefix === '' || str_starts_with($rule->getUid(), $prefix)) {
                    $uids[] = $rule->getUid();
                }
            }

            $offset += self::PAGE_SIZE;
        } while ($offset < $results->getTotal());

        return $uids;
    }

    /**
     * @param string $method
     * @param array|null $body
     * @return array
     * @throws \Exception
     */
    private function requestExperimentalFeatures(string $method, ?array $body = null): array
    {
        $options = ['headers' => ['Authorization' => 'Bearer ' . $this->serverSettings->getMasterKey()]];

        if ($body !== null) {
            $options['json'] = $body;
        }

        $response = $this->httpClient->request(
            $method,
            rtrim($this->serverSettings->getServerSettingsAddress(), '/') . '/experimental-features',
            $options
        );

        return (array) json_decode((string) $response->getBody(), true);
    }
}
