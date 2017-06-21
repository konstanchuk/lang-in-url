<?php

/**
 * Language In Url Extension for Magento 2
 *
 * @author     Volodymyr Konstanchuk http://konstanchuk.com
 * @copyright  Copyright (c) 2017 The authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace Konstanchuk\LangInUrl\Model\Request;

use Konstanchuk\LangInUrl\Helper\Data as Helper;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\StoreCookieManagerInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\Store;
use Magento\Framework\App\Area;
use Magento\Framework\UrlInterface;


class PathProcessor
{
    /** @var $_helper Helper */
    protected $_helper;

    /** @var  $_request RequestInterface */
    protected $_request;

    /** @var  $_storeManager StoreManagerInterface */
    protected $_storeManager;

    /* @var $_storeCookieManager StoreManagerInterface */
    protected $_storeCookieManager;

    /** @var $_httpContext HttpContext */
    protected $_httpContext;

    /** @var $_storeRepository StoreRepositoryInterface */
    protected $_storeRepository;

    /** @var $_urlBuilder UrlInterface */
    protected $_urlBuilder;

    public function __construct(
        Helper $helper,
        StoreManagerInterface $storeManager,
        RequestInterface $request,
        StoreCookieManagerInterface $storeCookieManager,
        HttpContext $httpContext,
        StoreRepositoryInterface $storeRepository,
        UrlInterface $urlBuilder
    )
    {
        $this->_helper = $helper;
        $this->_storeManager = $storeManager;
        $this->_request = $request;
        $this->_storeCookieManager = $storeCookieManager;
        $this->_httpContext = $httpContext;
        $this->_storeRepository = $storeRepository;
        $this->_urlBuilder = $urlBuilder;
    }

    public function process($path)
    {
        $indexFile = '/' . Helper::INDEX_FILE_NAME;
        if (substr($path, 0, strlen($indexFile)) == $indexFile) {
            $requestUri = substr($path, strlen($indexFile));
        } else {
            $requestUri = $path;
        }

        try {
            $currentStoreId = null;
            $languages = $this->_helper->getAllLanguageCodes();
            foreach ($languages as $scopeId => $lang) {
                $lang = '/' . $lang;
                if (substr($requestUri, 0, strlen($lang)) === $lang) {
                    $currentStoreId = $scopeId;
                    $requestUri = substr($requestUri, strlen($lang));
                    break;
                }
            }

            /** @var Store $store */
            if ($currentStoreId) {
                $store = $this->_storeRepository->getActiveStoreById($currentStoreId);
            } else {
                $store = $this->_storeManager->getDefaultStoreView();
            }

            /* @var \Magento\Store\Model\Store $currentActiveStore */
            $currentActiveStore = $this->_storeManager->getStore();
            /* @var \Magento\Store\Model\Store $defaultStoreView */
            $defaultStoreView = $this->_storeManager->getDefaultStoreView();

            $currentUrl = $this->_urlBuilder->getCurrentUrl();
            $normalUrl = $this->_helper->changeUrlForStore($currentUrl, $store);

            if ($store->getId() == $currentActiveStore->getId()) {
                if ($currentUrl != $normalUrl) {
                    $this->redirect($normalUrl);
                }
                return $requestUri;
            }

            if ($defaultStoreView->getId() == $store->getId()) {
                $this->_storeCookieManager->deleteStoreCookie($store);
            } else {
                $this->_httpContext->setValue(Store::ENTITY, $store->getCode(), $defaultStoreView->getCode());
                $this->_storeCookieManager->setStoreCookie($store);
            }
            $this->redirect($normalUrl);
            return $requestUri;
        } catch (\Exception $e) {
            return $path;
        }
    }

    protected function redirect($url)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $objectManager->get('Magento\Framework\App\State')->setAreaCode(Area::AREA_FRONTEND);
        $response = $objectManager->get('Magento\Framework\App\ResponseInterface');
        $response->setRedirect($url);
    }
}