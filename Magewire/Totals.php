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
use Magento\Quote\Api\Data\TotalsInterface;
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
    protected $listeners = [
        'applyCoupon' => 'collectTotals',
        'cartItemRemoved' => 'collectTotals',
        'removeCoupon' => 'collectTotals',
        'shippingMethodSelected' => 'collectTotals',
        'updateCart' => 'collectTotals',
        'updateQty' => 'collectTotals',
    ];

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
        $this->collectTotals();
    }

    /**
     * Get quote totals with tax and sort order information.
     *
     * @return void
     */
    public function collectTotals(): void
    {
        if (!($quote = $this->cartService->getQuote())) {
            return;
        }

        $checkoutConfig = $this->checkoutConfigProvider->getConfig();
        $quoteTotals = $quote->getTotals();
        $totalsSortOrder = $this->config->getTotalsSortOrder();
        $totalsData = $checkoutConfig['totalsData'];

        $this->priceDisplayModes = [
            'includeTaxInGrandTotal' => $checkoutConfig['includeTaxInGrandTotal'],
            'isZeroTaxDisplayed' => $checkoutConfig['isZeroTaxDisplayed'],
            'reviewShippingDisplayMode' => $checkoutConfig['reviewShippingDisplayMode'],
            'reviewTotalsDisplayMode' => $checkoutConfig['reviewTotalsDisplayMode'],
        ];

        $this->totals = [
            'subtotal' => [
                'title' => $quoteTotals[TotalsInterface::KEY_SUBTOTAL]->getTitle()->getText(),
                'value_incl_tax' => (float)$totalsData[TotalsInterface::KEY_SUBTOTAL_INCL_TAX],
                'value_excl_tax' => (float)$totalsData[TotalsInterface::KEY_SUBTOTAL],
                'sort_order' => $totalsSortOrder[TotalsInterface::KEY_SUBTOTAL],
            ],
            'shipping' => [
                'title' => $quoteTotals['shipping']->getTitle()->getText(),
                'value_incl_tax' => (float)$totalsData[TotalsInterface::KEY_SHIPPING_INCL_TAX],
                'value_excl_tax' => (float)$totalsData[TotalsInterface::KEY_SHIPPING_AMOUNT],
                'sort_order' => $totalsSortOrder['shipping'],
            ],
            'tax' => [
                'title' => $quoteTotals['tax']->getTitle()->getText(),
                'value' => (float)$totalsData[TotalsInterface::KEY_TAX_AMOUNT],
                'sort_order' => $totalsSortOrder['tax'],
            ],
            'grand_total' => [
                'title' => $quoteTotals[TotalsInterface::KEY_GRAND_TOTAL]->getTitle()->getText(),
                'value_incl_tax' => (float)($totalsData[TotalsInterface::KEY_GRAND_TOTAL] + $totalsData[TotalsInterface::KEY_TAX_AMOUNT]),
                'value_excl_tax' => (float)$totalsData[TotalsInterface::KEY_GRAND_TOTAL],
                'sort_order' => $totalsSortOrder[TotalsInterface::KEY_GRAND_TOTAL],
            ],
        ];

        if (isset($totalsData['total_segments']) && (float)$totalsData['discount_amount']) {
            foreach ($totalsData['total_segments'] as $totalSegment) {
                if ($totalSegment['code'] === 'discount') {
                    $this->totals['discount'] = [
                        'title' => $totalSegment['title'],
                        'value' => (float)$totalsData[TotalsInterface::KEY_DISCOUNT_AMOUNT],
                        'sort_order' => $totalsSortOrder['discount'],
                    ];
                }
            }
        }

        // sort by sort order for display purposes
        uasort($this->totals, function ($a, $b) { return $a['sort_order'] <=> $b['sort_order']; });
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
