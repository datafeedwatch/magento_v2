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

class Refresh extends Button
{
    public function execute()
    {
        try {
            $apiUser = $this->apiUser;
            $apiUser->loadDfwUser();
            $apiUser->createDfwUser();
            
            $this->messageManager->addSuccessMessage(__('%1 user has been refreshed', ApiUser::USER_NAME));
            
            return $this->getResponse()->setRedirect($this->_redirect->getRefererUrl());
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            
            return $this->getResponse()->setRedirect($this->_redirect->getRefererUrl());
        }
    }
}
