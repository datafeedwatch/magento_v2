<?php
/**
 * Created by Q-Solutions Studio
 * Date: 23.08.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Controller\Adminhtml\System\Config\Grid;

use DataFeedWatch\Connector\Controller\Adminhtml\System\Config\Grid;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class Render
 * @package DataFeedWatch\Connector\Controller\Adminhtml\System\Config\Grid
 */
class Render extends Grid
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        try {
            $page  = $this->getRequest()->getParam('page');
            $limit = $this->getRequest()->getParam('limit');
            
            /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
            $resultPage = $this->resultPageFactory->create();
            $block      = $resultPage->getLayout()
                                     ->getBlock('datafeed.grid.items')
                                     ->setPage($page)
                                     ->setLimit($limit)
                                     ->toHtml();
            $this->getResponse()->setBody($block);
        } catch (Exception $e) {
            $this->getResponse()->setBody($e->getMessage());
        }
    }
}
