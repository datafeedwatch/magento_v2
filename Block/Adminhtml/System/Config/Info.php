<?php
/**
 * Created by Q-Solutions Studio
 * Date: 28.02.18
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;
use DataFeedWatch\Connector\Helper\Data as DataHelper;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory as ScheduleCollection;

class Info extends Field
{
    /** @var DataHelper */
    private $dataHelper;

    /** @var ScheduleCollection */
    private $scheduleCollection;

    public function __construct(
        Context $context,
        DataHelper $dataHelper,
        ScheduleCollection $scheduleCollection,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->scheduleCollection = $scheduleCollection;
        parent::__construct($context, $data);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        return $this->dataHelper->getInstallationComplete() ? '' : parent::render($element);
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        if (!$this->getTemplate()) {
            $this->setTemplate('system/config/info.phtml');
        }

        return parent::_prepareLayout();
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setHtmlId($element->getData('html_id'));
        return $this->_toHtml();
    }

    /**
     * @return mixed
     */
    public function getScheduledTasks()
    {
        $collection = $this->scheduleCollection->create();
        $collection->addFieldToFilter('job_code', 'datafeedwatch_connector_installer');
        $collection->addFieldToFilter('status', ['in' => ['pending', 'running']]);

        return $collection;
    }
}
