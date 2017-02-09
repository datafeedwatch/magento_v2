<?php
/**
 * Created by Q-Solutions Studio
 * Date: 22.08.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Grid extends Field
{
    /**
     * @return string
     */
    public function getActionUrl()
    {
        return $this->getUrl('datafeedwatch/system_config_grid/render');
    }
    
    /**
     * @return string
     */
    public function getSaveInheritanceActionUrl()
    {
        return $this->getUrl('datafeedwatch/system_config_grid/saveInheritance');
    }
    
    /**
     * @return string
     */
    public function getSaveImportActionUrl()
    {
        return $this->getUrl('datafeedwatch/system_config_grid/saveImport');
    }
    
    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('system/config/grid.phtml');
        }
        
        return $this;
    }
    
    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        
        return $this->_toHtml();
    }
}
