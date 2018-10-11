<?php
/**
 * Created by Q-Solutions Studio
 * Date: 29.08.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Block\Adminhtml\System\Config\Form\Button;

/**
 * Class Open
 * @package DataFeedWatch\Connector\Block\Adminhtml\System\Config\Form\Button
 */
class Open extends BaseButton
{
    /**
     * @return \Magento\Framework\Phrase
     */
    public function getButtonLabel()
    {
        return __('Open');
    }

    /**
     * @return string
     */
    public function getButtonOnClick()
    {
        return sprintf("window.open('%s', '_blank')", $this->getUrl('datafeedwatch/system_config_button/open'));
    }
}
