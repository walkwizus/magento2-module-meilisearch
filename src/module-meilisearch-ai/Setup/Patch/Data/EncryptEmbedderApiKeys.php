<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Walkwizus\MeilisearchAi\Model\ApiKeyEncryption;

class EncryptEmbedderApiKeys implements DataPatchInterface
{
    private const TABLE = 'meilisearch_ai_embedder';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ApiKeyEncryption $apiKeyEncryption
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly ApiKeyEncryption $apiKeyEncryption
    ) {
    }

    /**
     * @return $this
     */
    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable(self::TABLE);
        $select = $connection->select()
            ->from($table, ['embedder_id', 'api_key'])
            ->where('api_key IS NOT NULL')
            ->where('api_key != ?', '');

        foreach ($connection->fetchPairs($select) as $embedderId => $apiKey) {
            if ($this->apiKeyEncryption->isEncrypted((string) $apiKey)) {
                continue;
            }

            $connection->update(
                $table,
                ['api_key' => $this->apiKeyEncryption->encrypt((string) $apiKey)],
                ['embedder_id = ?' => (int) $embedderId]
            );
        }

        return $this;
    }

    /**
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }
}
