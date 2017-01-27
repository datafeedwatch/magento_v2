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

class UpdateLastInheritanceUpdateDate
    implements ObserverInterface
{
    /** @var DataHelper */
    protected $dataHelper;
    
    /**
     * @param DataHelper $dataHelper
     */
    public function __construct(DataHelper $dataHelper) {
        
        $this->dataHelper = $dataHelper;
    }
    
    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer) {
        $this->dataHelper->updateLastInheritanceUpdateDate();
    }
}