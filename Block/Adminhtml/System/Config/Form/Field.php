<?php
/**
 * Created by Q-Solutions Studio
 * Date: 28.02.18
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Block\Adminhtml\System\Config\Form;

use Magento\Backend\Block\Template\Context;
use DataFeedWatch\Connector\Helper\Data as DataHelper;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Field
 * @package DataFeedWatch\Connector\Block\Adminhtml\System\Config\Form
 */
class Field extends \Magento\Config\Block\System\Config\Form\Field
{

    /** @var DataHelper */
    private $dataHelper;

    /**
     * Field constructor.
     * @param Context $context
     * @param DataHelper $dataHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        DataHelper $dataHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }
}
