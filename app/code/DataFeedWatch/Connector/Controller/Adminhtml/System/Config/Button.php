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

abstract class Button
    extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'DataFeedWatch_Connector::config';
    
    /** @var \Magento\Framework\Registry */
    protected $coreRegistry;
    
    /** @var \DataFeedWatch\Connector\Helper\Data */
    protected $dataHelper;
    
    /** @var \DataFeedWatch\Connector\Model\Api\User */
    protected $apiUser;
    
    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $messageManager;
    
    /**
     * @param Action\Context                              $context
     * @param \Magento\Framework\Registry                 $coreRegistry
     * @param \DataFeedWatch\Connector\Helper\Data        $dataHelper
     * @param \DataFeedWatch\Connector\Model\Api\User     $apiUser
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \DataFeedWatch\Connector\Helper\Data $dataHelper,
        \DataFeedWatch\Connector\Model\Api\User $apiUser,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->coreRegistry   = $coreRegistry;
        $this->dataHelper     = $dataHelper;
        $this->apiUser        = $apiUser;
        $this->messageManager = $messageManager;
        parent::__construct($context);
    }
}
