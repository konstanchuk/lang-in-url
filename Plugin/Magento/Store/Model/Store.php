<?php

/**
 * Language In Url Extension for Magento 2
 *
 * @author     Volodymyr Konstanchuk http://konstanchuk.com
 * @copyright  Copyright (c) 2017 The authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace Konstanchuk\LangInUrl\Plugin\Magento\Store\Model;

use Magento\Framework\UrlInterface;
use Konstanchuk\LangInUrl\Helper\Data as Helper;


class Store
{
    /** @var $_helper Helper */
    protected $_helper;

    public function __construct(
        Helper $helper
    )
    {
        $this->_helper = $helper;
    }

    public function aroundGetBaseUrl(
        \Magento\Store\Model\Store $subject,
        \Closure $proceed,
        $type = UrlInterface::URL_TYPE_LINK,
        $secure = null
    ) {
        $result = $proceed($type, $secure);
        if ($this->_helper->isEnabled() && $type == UrlInterface::URL_TYPE_LINK) {
            $result = $this->_helper->addLanguageToUrl($result, $subject);
        }
        return $result;
    }

    public function afterGetStorePath($subject, $result)
    {
        if ($this->_helper->isEnabled()) {
            return '/';
        }
        return $result;
    }
}