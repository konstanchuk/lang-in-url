<?php

/**
 * Language In Url Extension for Magento 2
 *
 * @author     Volodymyr Konstanchuk http://konstanchuk.com
 * @copyright  Copyright (c) 2017 The authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace Konstanchuk\LangInUrl\Plugin\Magento\Store\App\Request;

use Konstanchuk\LangInUrl\Helper\Data as Helper;
use Konstanchuk\LangInUrl\Model\Request\PathProcessor;


class PathInfoProcessor
{
    /** @var Helper */
    protected $_helper;

    /** @var PathProcessor */
    protected $_pathProcessor;

    public function __construct(
        Helper $helper,
        PathProcessor $pathProcessor
    )
    {
        $this->_helper = $helper;
        $this->_pathProcessor = $pathProcessor;
    }

    public function afterProcess($subject, $result)
    {
        if (!$this->_helper->isEnabled()) {
            return $result;
        }
        if (strpos($result, Helper::STORE_SWITCH_URI) === false) {
            return $this->_pathProcessor->process($result);
        }
        return Helper::STORE_SWITCH_URI;
    }
}