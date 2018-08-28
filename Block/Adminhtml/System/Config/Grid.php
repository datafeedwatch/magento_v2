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
use Magento\Backend\Block\Template\Context;
use DataFeedWatch\Connector\Helper\Data as DataHelper;

/**
 * Class Grid
 * @package DataFeedWatch\Connector\Block\Adminhtml\System\Config
 */
class Grid extends Field
{
    /** @var DataHelper */
    private $dataHelper;

    public function __construct(
        Context $context,
        DataHelper $dataHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }

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
    public function _prepareLayout()
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
    public function _getElementHtml(AbstractElement $element)
    {
        $this->setHtmlId($element->getData('html_id'));
        return $this->_toHtml();
    }
}
