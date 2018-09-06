<?php
/**
 * Created by Q-Solutions Studio
 * Date: 25.08.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Block\Adminhtml\System\Config\Grid;

use Magento\Framework\View\Element\Template;

/**
 * Class Items
 * @package DataFeedWatch\Connector\Block\Adminhtml\System\Config\Grid
 */
class Items extends Template
{
    /**
     * @param $attribute
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getItemRow($attribute)
    {
        
        return $this->getLayout()->getBlock('datafeed.grid.items.row')->setAttributeItem($attribute)->toHtml();
    }

    /**
     * @param $page
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setPage($page)
    {
        if (!empty($page)) {
            $this->getPager()->setPage($page);
        }
        
        return $this;
    }

    /**
     * @return bool|\Magento\Framework\View\Element\BlockInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPager()
    {
        return $this->getLayout()->getBlock('datafeed.grid.pager');
    }

    /**
     * @param $limit
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setLimit($limit)
    {
        if (!empty($limit)) {
            $this->getPager()->setLimit($limit);
        }
        
        return $this;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPagerHtml()
    {
        
        return $this->getPager()->toHtml();
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCollection()
    {
        
        return $this->getPager()->getCollection();
    }
}
