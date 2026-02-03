<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Controller\Adminhtml\Embedders;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Forward;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

class NewAction extends Action
{
    public const ADMIN_RESOURCE = 'Walkwizus_MeilisearchAi::embedders';

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        /** @var Forward $resultForward */
        $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
        return $resultForward->forward('edit');
    }
}
