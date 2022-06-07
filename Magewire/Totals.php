<?php
/**
 * Copyright © element119. All rights reserved.
 * See LICENCE.txt for licence details.
 */
declare(strict_types=1);

namespace Element119\MagewireCart\Magewire;

use Element119\MagewireCart\Scope\Config;
use Element119\MagewireCart\Service\Cart as CartService;
use Magento\Checkout\Model\CompositeConfigProvider as CheckoutConfigProvider;
use Magewirephp\Magewire\Component;

class Totals extends Component
{
    /** @var Config */
    private Config $config;

    /** @var CartService */
    private CartService $cartService;

    /** @var CheckoutConfigProvider */
    private CheckoutConfigProvider $checkoutConfigProvider;

    /** Magewire Component Properties */
    public array $totals = [];
    public array $priceDisplayModes = [];

    /**
     * @param Config $config
     * @param CartService $cartService
     * @param CheckoutConfigProvider $checkoutConfigProvider
     */
    public function __construct(
        Config $config,
        CartService $cartService,
        CheckoutConfigProvider $checkoutConfigProvider
    ) {
        $this->config = $config;
        $this->cartService = $cartService;
        $this->checkoutConfigProvider = $checkoutConfigProvider;
    }

    /**
     * Component initial state setup.
     *
     * @return void
     */
    public function mount(): void
    {
        $totalsSortOrder = $this->config->getTotalsSortOrder();
        $checkoutConfig = $this->checkoutConfigProvider->getConfig();
        $totalsData = $checkoutConfig['totalsData'];
        $totalsSegments = $totalsData ? $totalsData['total_segments'] : [];

        if (!$checkoutConfig || !$totalsSortOrder || !$totalsData || !$totalsSegments) {
            return;
        }

        $this->priceDisplayModes = [
            'includeTaxInGrandTotal' => $checkoutConfig['includeTaxInGrandTotal'],
            'isZeroTaxDisplayed' => $checkoutConfig['isZeroTaxDisplayed'],
            'reviewShippingDisplayMode' => $checkoutConfig['reviewShippingDisplayMode'],
            'reviewTotalsDisplayMode' => $checkoutConfig['reviewTotalsDisplayMode'],
        ];

        asort($totalsSortOrder);

        foreach ($totalsSortOrder as $totalType => $sortOrder) {
            foreach ($totalsSegments as $segment) {
                if ($totalType === $segment['code']) {
                    $this->totals[$totalType] = [
                        'title' => $segment['title'],
                        'value' => $totalsData[$totalType] ?? $totalsData["{$totalType}_amount"],
                        'value_incl_tax' => $segment['value'],
                        'extension_attributes' => $segment['extension_attributes'],
                    ];
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function isQuoteVirtual(): bool
    {
        return $this->cartService->getQuote() && $this->cartService->getQuote()->isVirtual();
    }

    /**
     * @return bool
     */
    public function hasShippingMethod(): bool
    {
        return $this->cartService->getQuote()
            && $this->cartService->getQuote()->getShippingAddress()->getShippingMethod();
    }
}
