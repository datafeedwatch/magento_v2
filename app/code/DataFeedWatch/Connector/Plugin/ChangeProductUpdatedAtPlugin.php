<?php
/**
 * Created by Q-Solutions Studio
 * Date: 05.09.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Plugin;

class ChangeProductUpdatedAtPlugin
{
    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product $product
     * @param \Magento\Framework\Model\AbstractModel       $object
     */
    public function beforeSave(\Magento\Catalog\Model\ResourceModel\Product $product,
                               \Magento\Framework\Model\AbstractModel $object) {
        $sql = sprintf('UPDATE %s SET `updated_at` = \'%s\' WHERE `entity_id` = %s',
            $product->getEntityTable(), gmdate('Y-m-d H:i:s'), $object->getId());
        $product->getConnection()->query($sql);
    }
}