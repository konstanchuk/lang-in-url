<?php

/**
 * Language In Url Extension for Magento 2
 *
 * @author     Volodymyr Konstanchuk http://konstanchuk.com
 * @copyright  Copyright (c) 2017 The authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace Konstanchuk\LangInUrl\Magento\Framework\App\Request;

use Magento\Framework\App\Request\Http as CoreHttpRequest;


class Http extends CoreHttpRequest
{
    /* because plugin not working on \Magento\Framework\App\Request\Http */
    public function setPathInfo($pathInfo = null)
    {
        $return = parent::setPathInfo($pathInfo);
        $helper = $this->objectManager->get('Konstanchuk\LangInUrl\Helper\Data');
        if (is_null($pathInfo)
            && $helper->isEnabled()
            && $this->getRequestUri() == '/') {
            /** @var \Konstanchuk\LangInUrl\Model\Request\PathProcessor $pathProcessor */
            $pathProcessor = $this->objectManager->get('Konstanchuk\LangInUrl\Model\Request\PathProcessor');
            $pathProcessor->process('/');
        }
        return $return;
    }
}