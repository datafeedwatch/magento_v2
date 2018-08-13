<?php
/**
 * Created by Q-Solutions Studio
 * Date: 18.08.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Api;

interface ConnectorInterface
{
    /**
     * Retrieve extension version
     *
     * @api
     * @return float
     */
    public function version();

    /**
     * Retrieve datetime in GMT
     *
     * @api
     * @return int
     */
    public function gmtOffset();

    /**
     * Retrieve stores
     *
     * @api
     * @return string[]
     */
    public function stores();

    /**
     * Retrieve products
     *
     * @api
     *
     * @param string $store = null
     * @param string[] $type = string[]
     * @param string $status = null
     * @param int $per_page = 100
     * @param int $page = 1
     *
     * @return string[]
     */
    public function products(
        $store = null,
        $type = [],
        $status = null,
        $per_page = 100,
        $page = 1);

    /**
     * Retrieve product count
     *
     * @api
     *
     * @param string $store = null
     * @param string[] $type = string[]
     * @param string $status = null
     * @param int $per_page = 100
     * @param int $page = 1
     *
     * @return int
     */
    public function productCount(
        $store = null,
        $type = [],
        $status = null,
        $per_page = 100,
        $page = 1
    );

    /**
     * Retrieve products based on last update
     *
     * @api
     *
     * @param string $store = null
     * @param string[] $type = string[]
     * @param string $status = null
     * @param string $timezone = null
     * @param string $from_date = null
     * @param int $per_page = 100
     * @param int $page = 1
     *
     * @return string[]
     */
    public function updatedProducts(
        $store = null,
        $type = [],
        $status = null,
        $timezone = null,
        $from_date = null,
        $per_page = 100,
        $page = 1
    );

    /**
     * Retrieve product count based on last update
     *
     * @api
     *
     * @param string $store = null
     * @param string[] $type = string[]
     * @param string $status = null
     * @param string $timezone = null
     * @param string $from_date = null
     * @param int $per_page = 100
     * @param int $page = 1
     *
     * @return int
     */
    public function updatedProductCount(
        $store = null,
        $type = [],
        $status = null,
        $timezone = null,
        $from_date = null,
        $per_page = 100,
        $page = 1
    );

    /**
     * Retrieve Product Ids
     *
     * @api
     *
     * @param string $store = null
     * @param string[] $type = string[]
     * @param string $status = null
     * @param string $timezone = null
     * @param string $from_date = null
     * @param int $per_page = 100
     * @param int $page = 1
     *
     * @return string[]
     */
    public function productIds(
        $store = null,
        $type = [],
        $status = null,
        $timezone = null,
        $from_date = null,
        $per_page = 100,
        $page = 1
    );

    /**
     * revoke DFW admin user
     *
     * @api
     *
     * @param string $token = null
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function revokeAccessToken($token = null);
}
