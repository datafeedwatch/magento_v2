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
 * Class Add
 * @package DataFeedWatch\Connector\Controller\Adminhtml\System\Config\Button
 */
class Add extends Button
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
            $apiUser->createDfwUser();
            
            return $this->getResponse()->setRedirect($apiUser->getRegisterUrl());
        } catch (Exception $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
            
            return $this->getResponse()->setRedirect($this->_redirect->getRefererUrl());
        }
    }
}
