<?php
/**
 * Created by Q-Solutions Studio
 * Date: 31.08.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Observer;

use DataFeedWatch\Connector\Helper\Data as DataHelper;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class RemoveProductFromUpdatedTable
 * @package DataFeedWatch\Connector\Observer
 */
class RemoveProductFromUpdatedTable implements ObserverInterface
{
    /** @var DataHelper */
    public $dataHelper;
    
    /** @var \Magento\Framework\App\ResourceConnection */
    public $resource;
    
    /**
     * @param DataHelper                                $dataHelper
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        DataHelper $dataHelper,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        
        $this->dataHelper = $dataHelper;
        $this->resource   = $resource;
    }
    
    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Magento\Catalog\Model\Product $category */
        $product    = $observer->getProduct();
        $connection = $this->resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
        $connection->delete(
            $this->resource->getTableName('datafeedwatch_updated_products'),
            sprintf('dfw_prod_id = %s', $product->getId())
        );
    }
}
