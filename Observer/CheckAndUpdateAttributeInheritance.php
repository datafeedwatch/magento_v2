<?php
/**
 * Created by Q-Solutions Studio
 * Date: 01.09.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Observer;

use DataFeedWatch\Connector\Helper\Data as DataHelper;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class CheckAndUpdateAttributeInheritance
 * @package DataFeedWatch\Connector\Observer
 */
class CheckAndUpdateAttributeInheritance implements ObserverInterface
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
        $attribute = $observer->getAttribute();
        if ($attribute->getForceSave() !== true) {
            if (!$attribute->getCanConfigureImport()
                && !$attribute->isObjectNew()
                && $attribute->getImportToDfw() !== null) {
                $attribute->setImportToDfw($attribute->getOrigData('import_to_dfw'));
            }
            if ($attribute->hasCanConfigureInheritance() && !$attribute->getCanConfigureInheritance()
                && !$attribute->getInheritance() !== null && !$attribute->isObjectNew()) {
                $attribute->setInheritance($attribute->getOrigData('inheritance'));
            }
        }

        if ($this->canSaveUpdateDate($attribute)) {
            $this->dataHelper->updateLastInheritanceUpdateDate();
        }
    }

    /**
     * @param $attribute
     *
     * @return bool
     */
    private function canSaveUpdateDate($attribute)
    {
        return ($attribute->dataHasChangedFor('inheritance') && (int) $attribute->getOrigData('import_to_dfw') === 1)
               || $attribute->dataHasChangedFor('import_to_dfw')
               || (int) $attribute->getData('import_to_dfw') === 1
               || $attribute->isObjectNew();
    }
}
