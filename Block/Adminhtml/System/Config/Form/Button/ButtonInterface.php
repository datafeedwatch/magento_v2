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

/**
 * Interface ButtonInterface
 * @package DataFeedWatch\Connector\Block\Adminhtml\System\Config\Form\Button
 */
interface ButtonInterface
{
    public function getButtonLabel();
    public function getButtonOnClick();
}
