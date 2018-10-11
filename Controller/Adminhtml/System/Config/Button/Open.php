<?php
/**
 * Created by Q-Solutions Studio
 * Date: 29.08.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Controller\Adminhtml\System\Config\Button;

use DataFeedWatch\Connector\Controller\Adminhtml\System\Config\Button;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class Open
 * @package DataFeedWatch\Connector\Controller\Adminhtml\System\Config\Button
 */
class Open extends Button
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        try {
            $apiUser = $this->apiUser;
            $apiUser->loadDfwUser();
            
            if (!$apiUser->isObjectNew()) {
                return $this->getResponse()->setRedirect($this->dataHelper->getDataFeedWatchUrl());
            }
            
            $apiUser->createDfwUser();
            
            return $this->getResponse()->setRedirect($apiUser->getRegisterUrl());
        } catch (Exception $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
            
            return $this->getResponse()->setRedirect($this->_redirect->getRefererUrl());
        }
    }
}
