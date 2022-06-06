<?php
/**
 * Copyright © element119. All rights reserved.
 * See LICENCE.txt for licence details.
 */
declare(strict_types=1);

namespace Element119\MagewireCart\Scope;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_DEFAULT_COUNTRY = 'general/country/default';

    /** @var ScopeConfigInterface */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param int|string|null $scopeCode
     * @return string
     */
    public function getDefaultCountry($scopeCode = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_DEFAULT_COUNTRY,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }
}
