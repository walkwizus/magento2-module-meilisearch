<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Model\AbstractModel;

class Embedder extends AbstractDb
{
    /**
     * @param Context $context
     * @param EntityManager $entityManager
     * @param $connectionName
     */
    public function __construct(
        Context $context,
        private readonly EntityManager $entityManager,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
    }

    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('meilisearch_ai_embedder', 'embedder_id');
    }

    /**
     * @param AbstractModel $object
     * @return $this|Embedder
     * @throws \Exception
     */
    public function save(AbstractModel $object)
    {
        $this->entityManager->save($object);
        return $this;
    }
}
