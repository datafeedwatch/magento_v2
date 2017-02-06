<?php
/**
 * Created by Q-Solutions Studio
 * Date: 30.08.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Controller\Adminhtml\System\Config\Button;

use DataFeedWatch\Connector\Controller\Adminhtml\System\Config\Button;
use Symfony\Component\Config\Definition\Exception\Exception;

class Restore extends Button
{
    public function execute()
    {
        try {
            $this->dataHelper->restoreOriginalAttributesConfig();
            
            $this->messageManager->addSuccessMessage(__('Original inheritance configuration has been restored'));
            
            return $this->getResponse()->setRedirect($this->_redirect->getRefererUrl());
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            
            return $this->getResponse()->setRedirect($this->_redirect->getRefererUrl());
        }
    }
}
