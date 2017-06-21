<?php

/**
 * Language In Url Extension for Magento 2
 *
 * @author     Volodymyr Konstanchuk http://konstanchuk.com
 * @copyright  Copyright (c) 2017 The authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace Konstanchuk\LangInUrl\Model\System\Config;

use Magento\Framework\Option\ArrayInterface;


class StoreSwitch implements ArrayInterface
{
    const CHANGE_REDIRECT_FOR_STORE = 0;
    const REDIRECT_ON_BASE_URL = 1;

    public function toOptionArray()
    {
        return [
            static::CHANGE_REDIRECT_FOR_STORE => __('change url for store'),
            static::REDIRECT_ON_BASE_URL => __('redirect on base url'),
        ];
    }
}