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
    public function gmt_offset();

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
     * @param int $perPage = 100
     * @param int $page = 1
     *
     * @return string[]
     */
    public function products($store = null, $type = array(), $status = null, $perPage = 100, $page = 1);

    /**
     * Retrieve product count
     *
     * @api
     *
     * @param string $store = null
     * @param string[] $type = string[]
     * @param string $status = null
     * @param int $perPage = 100
     * @param int $page = 1
     *
     * @return int
     */
    public function product_count($store = null, $type = array(), $status = null, $perPage = 100, $page = 1);

    /**
     * Retrieve products based on last update
     *
     * @api
     *
     * @param string $store = null
     * @param string[] $type = string[]
     * @param string $status = null
     * @param string $timezone = null
     * @param string $fromDate = null
     * @param int $perPage = 100
     * @param int $page = 1
     *
     * @return string[]
     */
    public function updated_products($store = null, $type = array(), $status = null, $timezone = null, $fromDate = null, $perPage = 100, $page = 1);

    /**
     * Retrieve product count based on last update
     *
     * @api
     *
     * @param string $store = null
     * @param string[] $type = string[]
     * @param string $status = null
     * @param string $timezone = null
     * @param string $fromDate = null
     * @param int $perPage = 100
     * @param int $page = 1
     *
     * @return int
     */
    public function updated_product_count($store = null, $type = array(), $status = null, $timezone = null, $fromDate = null, $perPage = 100, $page = 1);

    /**
     * Retrieve Product Ids
     *
     * @api
     *
     * @param string $store = null
     * @param string[] $type = string[]
     * @param string $status = null
     * @param string $timezone = null
     * @param string $fromDate = null
     * @param int $perPage = 100
     * @param int $page = 1
     *
     * @return string[]
     */
    public function product_ids($store = null, $type = array(), $status = null, $timezone = null, $fromDate = null, $perPage = 100, $page = 1);

    /**
     * revoke DFW admin user
     *
     * @api
     *
     * @param string $token = null
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function revoke_access_token($token = null);
}
