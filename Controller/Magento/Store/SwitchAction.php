<?php

/**
 * Language In Url Extension for Magento 2
 *
 * @author     Volodymyr Konstanchuk http://konstanchuk.com
 * @copyright  Copyright (c) 2017 The authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace Konstanchuk\LangInUrl\Controller\Magento\Store;

use Magento\Framework\App\Action\Context as ActionContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Session\SessionManager;
use Magento\Store\Api\StoreCookieManagerInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\StoreIsInactiveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreResolver;
use Magento\Store\Model\Store;
use Magento\Framework\Session\SessionManagerInterface;
use Konstanchuk\LangInUrl\Helper\Data as Helper;
use Konstanchuk\LangInUrl\Model\System\Config\StoreSwitch as StoreSwitchTypes;


class SwitchAction extends \Magento\Store\Controller\Store\SwitchAction
{
    /** @var  Helper */
    protected $_helper;

    /** @var  SessionManager */
    protected $_sessionManager;

    public function __construct(
        ActionContext $context,
        StoreCookieManagerInterface $storeCookieManager,
        HttpContext $httpContext,
        StoreRepositoryInterface $storeRepository,
        StoreManagerInterface $storeManager,
        SessionManagerInterface $sessionManager,
        Helper $helper
    )
    {
        parent::__construct($context, $storeCookieManager, $httpContext, $storeRepository, $storeManager);
        $this->_sessionManager = $sessionManager;
        $this->_helper = $helper;
    }

    public function execute()
    {
        if (!$this->_helper->isEnabled()) {
            parent::execute();
            return;
        }

        $currentActiveStore = $this->storeManager->getStore();
        $storeCode = $this->_request->getParam(
            StoreResolver::PARAM_NAME,
            $this->storeCookieManager->getStoreCodeFromCookie()
        );

        try {
            /** @var $store Store */
            $store = $this->storeRepository->getActiveStoreByCode($storeCode);

            $defaultStoreView = $this->storeManager->getDefaultStoreView();
            if ($defaultStoreView->getId() == $store->getId()) {
                $this->storeCookieManager->deleteStoreCookie($store);
            } else {
                $this->httpContext->setValue(Store::ENTITY, $store->getCode(), $defaultStoreView->getCode());
                $this->storeCookieManager->setStoreCookie($store);
            }

            switch ($this->_helper->getStoreSwitchType()) {
                case StoreSwitchTypes::CHANGE_REDIRECT_FOR_STORE:
                    $redirectUrl = $this->_helper->changeUrlForStore($this->_redirect->getRedirectUrl(), $store);
                    break;
                default:
                    $redirectUrl = $store->getBaseUrl();
                    break;
            }
            $this->getResponse()->setRedirect($redirectUrl);
        } catch (StoreIsInactiveException $e) {
            $error = __('Requested store is inactive');
        } catch (NoSuchEntityException $e) {
            $error = __('Requested store is not found');
        } catch (\Exception $e) {
            $error = __('error');
        }

        if (isset($error)) {
            $this->messageManager->addErrorMessage($error);
            $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl());
            return;
        }

    }
}