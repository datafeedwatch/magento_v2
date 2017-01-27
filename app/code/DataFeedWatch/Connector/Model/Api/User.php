<?php
/**
 * Created by Q-Solutions Studio
 * Date: 29.08.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Model\Api;

use Magento\Authorization\Model\UserContextInterface;
use Magento\User\Model\User as MagentoUser;

class User
    extends MagentoUser
{
    const API_KEY_SHUFFLE_STRING = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const API_KEY_LENGTH         = 32;
    const USER_NAME              = 'datafeedwatch';
    const USER_FIRST_NAME        = 'Api Access';
    const USER_LAST_NAME         = 'DataFeedWatch';
    const USER_EMAIL             = 'magento@datafeedwatch.com';
    const USER_IS_ACTIVE         = 1;
    const ROLE_NAME              = 'DataFeedWatch';
    const ROLE_TYPE              = 'G';
    const ROLE_PID               = false;
    const RULE_RESOURCES         = ['Magento_Backend::all'];
    const RULE_PRIVILEGES        = '';
    const RULE_PERMISSION        = 'allow';
    /** @var \DataFeedWatch\Connector\Helper\Data */
    protected $dataHelper;
    /** @var \Magento\Authorization\Model\RoleFactory */
    protected $roleFactory;
    /** @var \Magento\Authorization\Model\RulesFactory */
    protected $_rulesFactory;
    /** @var string $decodedApiKey */
    private $decodedApiKey;
    private $oauthToken;
    private $adminTokenService;
    private $tokenModel;

    /**
     * User constructor.
     *
     * @param \Magento\Framework\Model\Context                                $context
     * @param \Magento\Framework\Registry                                     $registry
     * @param \Magento\User\Helper\Data                                       $userData
     * @param \Magento\Backend\App\ConfigInterface                            $config
     * @param \Magento\Authorization\Model\RoleFactory                        $roleFactory
     * @param \Magento\Framework\Validator\DataObjectFactory                  $validatorObjectFactory
     * @param \Magento\Authorization\Model\RulesFactory                       $rulesFactory
     * @param \Magento\Framework\Mail\Template\TransportBuilder               $transportBuilder
     * @param \Magento\Framework\Encryption\EncryptorInterface                $encryptor
     * @param \Magento\Store\Model\StoreManagerInterface                      $storeManager
     * @param \Magento\User\Model\UserValidationRules                         $validationRules
     * @param \DataFeedWatch\Connector\Helper\Data                            $dataHelper
     * @param \Magento\Integration\Model\ResourceModel\Oauth\Token\RequestLog $oauthToken
     * @param \Magento\Integration\Model\Oauth\Token                          $tokenModel
     * @param \Magento\Integration\Model\AdminTokenService                    $adminTokenService
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null    $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null              $resourceCollection
     * @param array                                                           $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\User\Helper\Data $userData,
        \Magento\Backend\App\ConfigInterface $config,
        \Magento\Authorization\Model\RoleFactory $roleFactory,
        \Magento\Framework\Validator\DataObjectFactory $validatorObjectFactory,
        \Magento\Authorization\Model\RulesFactory $rulesFactory,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\User\Model\UserValidationRules $validationRules,
        \DataFeedWatch\Connector\Helper\Data $dataHelper,
        \Magento\Integration\Model\ResourceModel\Oauth\Token\RequestLog $oauthToken,
        \Magento\Integration\Model\Oauth\Token $tokenModel,
        \Magento\Integration\Model\AdminTokenService $adminTokenService,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        
        $this->dataHelper           = $dataHelper;
        $this->_rulesFactory        = $rulesFactory;
        $this->oauthToken           = $oauthToken;
        $this->adminTokenService    = $adminTokenService;
        $this->tokenModel           = $tokenModel;
        parent::__construct($context, $registry, $userData, $config, $validatorObjectFactory, $roleFactory,
            $transportBuilder, $encryptor, $storeManager, $validationRules, $resource, $resourceCollection, $data);
    }
    
    public function createDfwUser()
    {
        $role = $this->createDfwUserRole();
        $this->generateApiKey();
        $this->addUserData();
        $this->setRoleId($role->getId());
        $this->save();

        $resource = array(
            'Magento_Catalog::config_catalog',
            'Magento_Backend::stores_attributes',
            'Magento_Catalog::attributes_attributes',
            'Magento_Catalog::update_attributes',
            'Magento_Catalog::sets',
            'Magento_Catalog::catalog_inventory',
            'Magento_Catalog::products',
            'Magento_Catalog::categories',
            'Magento_CatalogInventory::cataloginventory',
            'Magento_CatalogRule::promo_catalog',
            'DataFeedWatch_Connector::config',
            'Magento_Sales::sales',
        );

        $this->_rulesFactory->create()->setRoleId($role->getId())->setUserId($this->getId())->setResources($resource)->saveRel();
        $this->sendNewApiKeyToDfw();
    }
    
    protected function createDfwUserRole()
    {
        $role = $this->_roleFactory->create();
        $role->load(self::ROLE_NAME, 'role_name');
        
        $data = [
            'name'      => self::ROLE_NAME,
            'pid'       => self::ROLE_PID,
            'role_type' => self::ROLE_TYPE,
            'user_type' => UserContextInterface::USER_TYPE_ADMIN,
        ];
        
        $role->addData($data);
        $role->save();

        return $role;
    }
    
    protected function generateApiKey()
    {
        $this->decodedApiKey = sha1(time() . substr(str_shuffle(self::API_KEY_SHUFFLE_STRING), 0, self::API_KEY_LENGTH));
    }
    
    protected function addUserData()
    {
        $data = [
            'username'  => self::USER_NAME,
            'firstname' => self::USER_FIRST_NAME,
            'lastname'  => self::USER_LAST_NAME,
            'is_active' => self::USER_IS_ACTIVE,
            'password'  => $this->decodedApiKey,
            'email'     => self::USER_EMAIL,
        ];
        
        $this->addData($data);
    }
    
    protected function sendNewApiKeyToDfw()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getRegisterUrl());
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_exec($ch);
        curl_close($ch);
        $this->resetOauth();
    }
    
    /**
     * @return string
     */
    public function getRegisterUrl()
    {
        $registerUrl = sprintf('%splatforms/magento/sessions/finalize',
            $this->dataHelper->getDataFeedWatchUrl());
        
        return $registerUrl . '?shop=' . $this->_storeManager->getStore()->getBaseUrl() . '&token='
               . $this->getDecodedApiKey() . '&version=2';
    }

    public function resetOauth()
    {
        $this->oauthToken->resetFailuresCount(self::USER_NAME, \Magento\Integration\Model\Oauth\Token\RequestThrottler::USER_TYPE_ADMIN);
    }

    /**
     * @param null $token
     *
     * @return bool|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function revokeDfwUserAccessTokens($token = null)
    {
        $this->resetOauth();
        if (empty($token)) {
            $revoke = $this->adminTokenService->revokeAdminAccessToken($this->loadDfwUser()->getId());
            if ($revoke === true) {
                return 'Access tokens for DFW user have been revoked';
            } else {
                return $revoke;
            }
        } else if (is_string($token)) {
            $actualToken = $this->tokenModel->loadByToken($token);
            if ($actualToken->getId()) {
                $actualToken->setRevoked(1)->save();
                return 'Access token for DFW user have been revoked';
            }
        } else {
            return 'Token must be a string';
        }
        return false;
    }
    
    /**
     * @return string
     */
    public function getDecodedApiKey()
    {
        return $this->decodedApiKey;
    }
    
    public function deleteUserAndRole()
    {
        $role = $this->_roleFactory->create();
        $role->load(self::ROLE_NAME, 'role_name');
        $role->delete();
        $this->loadDfwUser();
        $this->delete();
    }
    
    /**
     * @return \DataFeedWatch\Connector\Model\Api\User
     */
    public function loadDfwUser()
    {
        return $this->load(self::USER_EMAIL, 'email');
    }
}
