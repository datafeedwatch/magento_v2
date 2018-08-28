<?php
/**
 * Created by Q-Solutions Studio
 * Date: 29.08.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Block\Adminhtml\System\Config\Grid;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as ProductAttributeCollectionCollection;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class Pager
 * @package DataFeedWatch\Connector\Block\Adminhtml\System\Config\Grid
 */
class Pager extends Template
{
    /** @var int */
    public $page = 1;
    
    /** @var int */
    public $limit = 20;
    
    /** @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection */
    public $attributeCollection;

    /**
     * Pager constructor.
     *
     * @param Context                              $context
     * @param ProductAttributeCollectionCollection $attributeCollection
     * @param array                                $data
     */
    public function __construct(
        Context $context,
        ProductAttributeCollectionCollection $attributeCollection,
        array $data = []
    ) {
        $this->attributeCollection = $attributeCollection;
        parent::__construct($context, $data);
    }

    /**
     * @return ProductAttributeCollectionCollection
     */
    public function getCollection()
    {
        $this->attributeCollection->addVisibleFilter()
            ->addFieldToFilter('additional_table.can_configure_inheritance', ['neq' => 0])
            ->addFieldToFilter('additional_table.import_to_dfw', ['neq' => 0])
            ->addFieldToFilter('additional_table.can_configure_inheritance', 1)
            ->setPageSize($this->limit)
            ->setCurPage($this->page);
        $this->attributeCollection->setOrder('frontend_label', 'asc');
        $this->attributeCollection->load();
        
        return $this->attributeCollection;
    }
    
    /**
     * @param int $page
     *
     * @return $this
     */
    public function setPage($page)
    {
        if (!empty($page) && is_numeric($page)) {
            $this->page = $page;
        }
        
        return $this;
    }
    
    /**
     * @return int
     */
    public function getPage()
    {
        return (int) $this->page;
    }
    
    /**
     * @param int $limit
     *
     * @return $this
     */
    public function setLimit($limit)
    {
        if (!empty($limit) && is_numeric($limit)) {
            $this->limit = $limit;
        }
        
        return $this;
    }
    
    /**
     * @return int
     */
    public function getLimit()
    {
        
        return (int) $this->limit;
    }
}
