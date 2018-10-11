<?php
/**
 * Created by Q-Solutions Studio
 * Date: 23.08.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;

/**
 * Class Grid
 * @package DataFeedWatch\Connector\Controller\Adminhtml\System\Config
 */
abstract class Grid extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'DataFeedWatch_Connector::config';
    
    /** @var \Magento\Framework\Registry */
    public $coreRegistry;
    
    /** @var \Magento\Framework\View\Result\PageFactory */
    public $resultPageFactory;
    
    /** @var \Magento\Catalog\Model\Product\Attribute\Repository */
    public $productAttributeRepository;
    
    /** @var \DataFeedWatch\Connector\Helper\Data */
    public $dataHelper;

    /**
     * Grid constructor.
     * @param Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Catalog\Model\Product\Attribute\Repository $productAttributeRepository
     * @param \DataFeedWatch\Connector\Helper\Data $dataHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Catalog\Model\Product\Attribute\Repository $productAttributeRepository,
        \DataFeedWatch\Connector\Helper\Data $dataHelper
    ) {
        $this->coreRegistry               = $coreRegistry;
        $this->resultPageFactory          = $resultPageFactory;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->dataHelper                 = $dataHelper;
        parent::__construct($context);
    }
}
