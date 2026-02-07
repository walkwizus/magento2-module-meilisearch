<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Link extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('meilisearch_ai_indices_embedder_link', 'index_uid');
    }

    /**
     * @param string $indexUid
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getEmbedderIdsByUid(string $indexUid): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), 'embedder_id')
            ->where('index_uid = ?', $indexUid);

        return $connection->fetchCol($select);
    }

    /**
     * @param string $indexUid
     * @param array $embedderIds
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function syncEmbedderLinks(string $indexUid, array $embedderIds): void
    {
        $connection = $this->getConnection();
        $table = $this->getMainTable();

        $connection->beginTransaction();
        try {
            $connection->delete($table, ['index_uid = ?' => $indexUid]);

            if (!empty($embedderIds)) {
                $data = [];
                foreach ($embedderIds as $id) {
                    if (empty($id)) {
                        continue;
                    }
                    $data[] = [
                        'index_uid' => $indexUid,
                        'embedder_id' => (int)$id
                    ];
                }

                if (!empty($data)) {
                    $connection->insertMultiple($table, $data);
                }
            }
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw new \Magento\Framework\Exception\LocalizedException(
                __('An error occurred while saving the index AI links: %1', $e->getMessage())
            );
        }
    }
}
