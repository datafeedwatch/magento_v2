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
use DataFeedWatch\Connector\Model\Api\User as ApiUser;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class Refresh
 * @package DataFeedWatch\Connector\Controller\Adminhtml\System\Config\Button
 */
class Refresh extends Button
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $apiUser = $this->apiUser;
            $apiUser->loadDfwUser();
            $apiUser->createDfwUser();
            
            $this->getMessageManager()->addSuccessMessage(__('%1 user has been refreshed', ApiUser::USER_NAME));
            
            return $this->getResponse()->setRedirect($this->_redirect->getRefererUrl());
        } catch (Exception $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
            
            return $this->getResponse()->setRedirect($this->_redirect->getRefererUrl());
        }
    }
}
