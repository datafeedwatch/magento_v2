<?php
/**
 * Created by Q-Solutions Studio
 * Date: 31.08.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Plugin;

use DataFeedWatch\Connector\Helper\Data as DataHelper;

class CategoryTreePlugin
{
    /** @var DataHelper */
    protected $dataHelper;
    
    public function __construct(DataHelper $dataHelper) {
        $this->dataHelper = $dataHelper;
    }
    
    public function afterMove(\Magento\Catalog\Model\Category $category) {
        $this->dataHelper->updateLastInheritanceUpdateDate();
    }
}