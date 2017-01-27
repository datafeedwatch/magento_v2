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

class Open
    extends Button
{
    public function execute() {
        try {
            $apiUser = $this->apiUser;
            $apiUser->loadDfwUser();
            
            if (!$apiUser->isObjectNew()) {
                
                return $this->getResponse()->setRedirect($this->dataHelper->getDataFeedWatchUrl());
            }
            
            $apiUser->createDfwUser();
            
            return $this->getResponse()->setRedirect($apiUser->getRegisterUrl());
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            
            return $this->getResponse()->setRedirect($this->_redirect->getRefererUrl());
        }
    }
}
