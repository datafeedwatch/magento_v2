<?php
/**
 * Created by Q-Solutions Studio
 * Date: 27.02.18
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Block\Adminhtml\System\Config\Form\Button;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;
use DataFeedWatch\Connector\Helper\Data as DataHelper;

abstract class BaseButton extends Field implements ButtonInterface
{
    /** @var DataHelper */
    private $dataHelper;

    public function __construct(
        Context $context,
        DataHelper $dataHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }
    /**
     * @param AbstractElement $element
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->getLayout()
                    ->createBlock('Magento\Backend\Block\Widget\Button')
                    ->setType('button')
                    ->setClass('scalable')
                    ->setLabel($this->getButtonLabel())
                    ->setOnClick($this->getButtonOnClick())
                    ->toHtml();
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        return !$this->dataHelper->getInstallationComplete() ? '' : parent::render($element);
    }
}
