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

class Force extends Button
{
    public function execute()
    {
        try {
            $this->dataHelper->updateLastInheritanceUpdateDate();
            $this->getMessageManager()->addSuccessMessage(__('All product data will be imported with next download'));
            
            return $this->getResponse()->setRedirect($this->_redirect->getRefererUrl());
        } catch (Exception $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
            
            return $this->getResponse()->setRedirect($this->_redirect->getRefererUrl());
        }
    }
}
