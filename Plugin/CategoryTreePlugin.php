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
use Magento\Catalog\Model\Category;

/**
 * Class CategoryTreePlugin
 * @package DataFeedWatch\Connector\Plugin
 */
class CategoryTreePlugin
{
    /** @var DataHelper */
    public $dataHelper;

    /**
     * CategoryTreePlugin constructor.
     * @param DataHelper $dataHelper
     */
    public function __construct(DataHelper $dataHelper)
    {
        $this->dataHelper = $dataHelper;
    }

    /**
     * @param Category $category
     */
    public function afterMove(Category $category)
    {
        if ($category instanceof Category) {
            $this->dataHelper->updateLastInheritanceUpdateDate();
        }
    }
}
