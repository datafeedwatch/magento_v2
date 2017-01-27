<?php
/**
 * Created by Q-Solutions Studio
 * Date: 29.08.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Controller\Adminhtml\System\Config\Grid;

use DataFeedWatch\Connector\Controller\Adminhtml\System\Config\Grid;
use Symfony\Component\Config\Definition\Exception\Exception;

class  SaveInheritance
    extends Grid
{
    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute() {
        try {
            $attributeCode = $this->getRequest()->getParam('attribute_code');
            $value         = $this->getRequest()->getParam('value');
            
            $attribute = $this->productAttributeRepository->get($attributeCode);
            $attribute->setInheritance($value)->save();
            
            $this->dataHelper->updateLastInheritanceUpdateDate();
        } catch (Exception $e) {
            $this->getResponse()->setBody($e->getMessage());
        }
    }
}
