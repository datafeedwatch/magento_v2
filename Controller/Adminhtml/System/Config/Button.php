<?php
/**
 * Created by Q-Solutions Studio
 * Date: 29.08.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;

/**
 * Class Button
 * @package DataFeedWatch\Connector\Controller\Adminhtml\System\Config
 */
abstract class Button extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'DataFeedWatch_Connector::config';
    
    /** @var \DataFeedWatch\Connector\Helper\Data */
    public $dataHelper;
    
    /** @var \DataFeedWatch\Connector\Model\Api\User */
    public $apiUser;

    /**
     * Button constructor.
     * @param Action\Context $context
     * @param \DataFeedWatch\Connector\Helper\Data $dataHelper
     * @param \DataFeedWatch\Connector\Model\Api\User $apiUser
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \DataFeedWatch\Connector\Helper\Data $dataHelper,
        \DataFeedWatch\Connector\Model\Api\User $apiUser
    ) {
        $this->dataHelper     = $dataHelper;
        $this->apiUser        = $apiUser;
        parent::__construct($context);
    }
}
