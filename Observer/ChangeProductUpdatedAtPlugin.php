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
 * Class ChangeProductUpdatedAtPlugin
 * @package DataFeedWatch\Connector\Observer
 */
class ChangeProductUpdatedAtPlugin implements ObserverInterface
{
    /** @var DataHelper */
    public $dataHelper;

    /** @var \Magento\Framework\App\ResourceConnection */
    public $resource;

    /**
     * @param DataHelper $dataHelper
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
        $date = gmdate('Y-m-d H:i:s');
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getProduct();
        if ('configurable' === $product->getTypeId()) {
            /** @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable $typeInstance */
            $typeInstance = $product->getTypeInstance();
            $childIds     = $typeInstance->getChildrenIds($product->getId());
            $childIds     = array_key_exists(0, $childIds) ? $childIds[0] : $childIds;
            $childIds     = !empty($childIds) ? array_values($childIds) : [];
            if (!empty($childIds)) {
                $childIds   = implode(',', $childIds);
                $connection = $this->resource->getConnection();
                $table      = $this->resource->getTableName('catalog_product_entity');
                $query      = "update {$table} set updated_at = '{$date}' where entity_id in ($childIds)";
                $connection->query($query);
            }
        }
        $product->setData('updated_at', $date);
    }
}
