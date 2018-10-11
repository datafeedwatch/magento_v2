<?php
/**
 * Created by Q-Solutions Studio
 * Date: 29.08.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Block\Adminhtml\System\Config\Grid\Items;

use DataFeedWatch\Connector\Model\System\Config\Source\Inheritance as InheritanceSource;
use Magento\Framework\View\Element\Template;

/**
 * Class Row
 * @package DataFeedWatch\Connector\Block\Adminhtml\System\Config\Grid\Items
 */
class Row extends Template
{
    /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute */
    public $attributeItem;
    
    /**
     * @return \DataFeedWatch\Connector\Model\System\Config\Source\Inheritance
     */
    public function getInheritanceSource()
    {
        return InheritanceSource::class;
    }
    
    /**
     * @return string
     */
    public function getAttributeLabel()
    {
        $attribute = $this->getAttributeItem();
        $label     = $attribute->getFrontendLabel();
        
        if (empty($label)) {
            $label = $attribute->getAttributeCode();
        }
        
        return $label;
    }
    
    /**
     * @return \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     */
    public function getAttributeItem()
    {
        
        return $this->attributeItem;
    }
    
    /**
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute
     *
     * @return $this
     */
    public function setAttributeItem($attribute)
    {
        $this->attributeItem = $attribute;
        
        return $this;
    }
    
    /**
     * @return string
     */
    public function getAttributeLink()
    {
        $attribute = $this->getAttributeItem();
        
        return $this->getUrl('catalog/product_attribute/edit', [
            'attribute_id' => $attribute->getId(),
        ]);
    }
}
