<?php

/**
 * Language In Url Extension for Magento 2
 *
 * @author     Volodymyr Konstanchuk http://konstanchuk.com
 * @copyright  Copyright (c) 2017 The authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace Konstanchuk\LangInUrl\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\Backend\App\Area\FrontNameResolver;


class Data extends AbstractHelper
{
    const XML_PATH_LANG_CODE = 'web/langinurl/code';
    const XML_PATH_IS_ENABLED = 'web/langinurl/enable';
    const XML_PATH_ALWAYS_USE_CODE = 'web/langinurl/always_use_code';
    const XML_PATH_STORE_SWITCH_TYPE = 'web/langinurl/store_switch_type';
    const XML_PATH_EXCLUDE_REQUEST_URI = 'web/langinurl/exclude_request_uri';

    const INDEX_FILE_NAME = 'index.php';

    const STORE_SWITCH_URI = '/stores/store/switch';

    /** @var ResourceConnection $_resourceConnection */
    protected $_resourceConnection;

    /** @var StoreManagerInterface $_storeManager */
    protected $_storeManager;

    /** @var FrontNameResolver  */
    protected $_frontNameResolver;

    protected $allLanguageCodes = null;
    protected $storesToLanguageCode = [];
    protected $isEnabled = null;

    public function __construct(
        Context $context,
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager,
        FrontNameResolver $frontNameResolver
    )
    {
        parent::__construct($context);
        $this->_resourceConnection = $resourceConnection;
        $this->_storeManager = $storeManager;
        $this->_frontNameResolver = $frontNameResolver;
    }

    public function getLanguageInUrl($store = ScopeInterface::SCOPE_STORE, $scopeCode = null)
    {
        return $this->scopeConfig->getValue(static::XML_PATH_LANG_CODE, $store, $scopeCode);
    }

    public function isEnabled()
    {
        if (is_null($this->isEnabled)) {
            if (isset($_SERVER['REQUEST_URI']) && $this->scopeConfig->getValue(static::XML_PATH_IS_ENABLED, ScopeInterface::SCOPE_WEBSITE)) {

                $this->isEnabled = true;

                if ($this->_storeManager->getStore()->getCode() == Store::ADMIN_CODE) {
                    $this->isEnabled = false;
                    return $this->isEnabled;
                }

                $excludeUris = $this->getExcludeRequestUri();
                foreach ($excludeUris as $item) {
                    if (0 === strpos($_SERVER['REQUEST_URI'], $item)) {
                        $this->isEnabled = false;
                        return $this->isEnabled;
                    }
                }

                $args = array_values(array_filter(explode('/', $_SERVER['REQUEST_URI'])));
                if (isset($args[0])) {
                    if ($args[0] == static::INDEX_FILE_NAME) {
                        array_shift($args);
                    }
                    if (isset($args[0])) {
                        $adminFrontName = $this->_frontNameResolver->getFrontName();
                        $this->isEnabled = $adminFrontName != $args[0];
                    }
                } else {
                    $this->isEnabled = true;
                }

                if ($this->isEnabled && count($this->getAllLanguageCodes()) > 1) {
                    $this->isEnabled = true;
                } else {
                    $this->isEnabled = false;
                }

            } else {
                $this->isEnabled = false;
            }
        }
        return $this->isEnabled;
    }

    public function canUseLanguageInDefaultStore()
    {
        return $this->scopeConfig->getValue(static::XML_PATH_ALWAYS_USE_CODE, ScopeInterface::SCOPE_WEBSITE);
    }

    public function getStoreSwitchType()
    {
        return $this->scopeConfig->getValue(static::XML_PATH_STORE_SWITCH_TYPE, ScopeInterface::SCOPE_WEBSITE);
    }

    public function getExcludeRequestUri()
    {
        $text = $this->scopeConfig->getValue(static::XML_PATH_EXCLUDE_REQUEST_URI, ScopeInterface::SCOPE_WEBSITE);
        return array_filter(array_map('trim', explode("\n", $text)));
    }

    public function unparseUrl(array $parsedUrl)
    {
        $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
        $host = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
        $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
        $user = isset($parsedUrl['user']) ? $parsedUrl['user'] : '';
        $pass = isset($parsedUrl['pass']) ? ':' . $parsedUrl['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
        $query = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
        $fragment = isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    protected function _changeUrl($url, $languageCode)
    {
        $parsedUrl = is_array($url) ? $url : parse_url($url);
        $endSlash = false;
        if (isset($parsedUrl['path'])) {
            $path = $parsedUrl['path'];
            if ($path[strlen($path) - 1] == '/') {
                $endSlash = true;
            }
            $explodedPath = explode('/', $path);
            $explodedPath = array_values(array_filter($explodedPath));

            $languages = array_values($this->getAllLanguageCodes());
            if (isset($explodedPath[0]) && in_array($explodedPath[0], $languages)) {
                if ($languageCode) {
                    $explodedPath[0] = $languageCode;
                } else {
                    unset($explodedPath[0]);
                }
            } else if ($languageCode) {
                array_unshift($explodedPath, $languageCode);
            }

            if ($languageCode && count($explodedPath) == 1) {
                $endSlash = true;
            }
            $path = implode('/', $explodedPath);
        } else {
            $path = $languageCode;
            if ($languageCode) {
                $endSlash = true;
            }
        }

        if (!empty($path)) {
            $parsedUrl['path'] = '/' . $path . ($endSlash ? '/' : '');
        } else if (isset($parsedUrl['path'])) {
            $parsedUrl['path'] = '/';
        }

        return $this->unparseUrl($parsedUrl);
    }

    public function changeUrlForStore($url, Store $store)
    {
        if ($this->isDefaultStore($store) && !$this->canUseLanguageInDefaultStore()) {
            $languageCode = null;
        } else {
            $languageCode = $this->getLanguageInUrl(ScopeInterface::SCOPE_STORE, $store->getCode());
        }
        return $this->_changeUrl($url, $languageCode);

    }

    public function removeLangFromUrl($url)
    {
        return $this->_changeUrl($url, null);
    }

    protected function isDefaultStore(Store $store)
    {
        $defaultStore = $this->_storeManager->getDefaultStoreView();
        return $store->getId() == $defaultStore->getId();
    }

    public function addLanguageToUrl($url, Store $store = null)
    {
        if (!$store instanceof Store) {
            $store = $this->_storeManager->getStore();
        }
        if ($this->isDefaultStore($store) && !$this->canUseLanguageInDefaultStore()) {
            return $url;
        }
        $languageCode = '/' . $this->getLanguageInUrl(ScopeInterface::SCOPE_STORE, $store->getCode());
        $url = preg_replace_callback(
            '/^((https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6}))(([\/\w \.-]*)*\/?)$/',
            function ($matches) use ($languageCode) {
                if (empty($matches[5]) || $matches[5] == '/') {
                    $path = $languageCode . '/';
                } else {
                    $path = $languageCode . $matches[5];
                }
                return $matches[1] . $path;
            },
            $url
        );
        return $url;
    }

    public function getAllLanguageCodes()
    {
        if (is_null($this->allLanguageCodes)) {
            $languageCodes = [];
            try {
                $connection = $this->_resourceConnection->getConnection(ResourceConnection::DEFAULT_CONNECTION);
                $configTable = $connection->getTableName('core_config_data');
                $storeTable = $connection->getTableName('store');
                $query = <<<SQL
                SELECT config.`scope_id` as scope_id, config.`value` as value
                FROM `$configTable` as config
                INNER JOIN `$storeTable` as store 
                  ON store.store_id = config.`scope_id` AND store.website_id = :website_id   
                WHERE config.`path` = :path AND config.`scope` = 'stores' AND store.`is_active` = 1
SQL;
                $result = $connection->fetchAll($query, [
                    ':path' => static::XML_PATH_LANG_CODE,
                    ':website_id' => $this->getWebsiteId(),
                ]);
                foreach ($result as $item) {
                    $value = trim($item['value']);
                    if (!$value) {
                        continue;
                    }
                    $languageCodes[$item['scope_id']] = $value;
                }
            } catch (\Exception $e) {
                $this->_logger->critical($e->getMessage());
            }
            $this->allLanguageCodes = $languageCodes;
        }
        return $this->allLanguageCodes;
    }

    public function getStoreByUrlLanguageCode($code)
    {
        if (isset($this->storesToLanguageCode[$code])) {
            return $this->storesToLanguageCode[$code];
        }
        $languages = $this->getAllLanguageCodes();
        $languages = array_flip($languages);
        if (isset($languages[$code])) {
            $store = $this->_storeManager->getStore($languages[$code]);
        } else {
            $store = null;
        }
        $this->storesToLanguageCode[$code] = $store;
        return $store;
    }

    protected function getWebsiteId()
    {
        return $this->_storeManager->getStore()->getWebsiteId();
    }
}