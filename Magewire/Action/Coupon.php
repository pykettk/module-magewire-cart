<?php
/**
 * Copyright © element119. All rights reserved.
 * See LICENCE.txt for licence details.
 */
declare(strict_types=1);

namespace Element119\MagewireCart\Magewire\Action;

use Element119\MagewireCart\Service\Cart as CartService;
use Magento\Checkout\Helper\Cart;
use Magento\SalesRule\Api\Data\CouponInterface;
use Magento\SalesRule\Model\CouponFactory;
use Magewirephp\Magewire\Component;

class Coupon extends Component
{
    /** @var CartService */
    private CartService $cartService;

    /** @var CouponFactory */
    private CouponFactory $couponFactory;

    /** Magewire Component Properties */
    public string $couponCode = '';
    public bool $isCouponApplied = false;

    /**
     * @param CartService $cartService
     * @param CouponFactory $couponFactory
     */
    public function __construct(
        CartService $cartService,
        CouponFactory $couponFactory
    ) {
        $this->cartService = $cartService;
        $this->couponFactory = $couponFactory;
    }

    /**
     * Set initial component state.
     *
     * @return void
     */
    public function mount(): void
    {
        if ($this->cartService->getQuote() && $couponCode = $this->cartService->getQuote()->getCouponCode()) {
            $this->couponCode = $couponCode;
            $this->isCouponApplied = true;
        }
    }

    /**
     * Apply the coupon code to the current quote.
     *
     * @return void
     */
    public function apply(): void
    {
        if (!$this->isCouponCodeValid()) {
            $this->dispatchErrorMessage(__('Invalid coupon code'));

            return;
        }

        if (!($quote = $this->cartService->getQuote())) {
            return;
        }

        $quote->setCouponCode($this->couponCode);
        $this->cartService->saveQuote($quote);
        $this->isCouponApplied = true;
        $this->dispatchSuccessMessage(__('Successfully applied coupon: %1', $this->couponCode));
    }

    /**
     * Remove the coupon code from the current quote.
     *
     * @return void
     */
    public function remove(): void
    {
        if (!($quote = $this->cartService->getQuote())) {
            return;
        }

        $quote->setCouponCode('');
        $this->cartService->saveQuote($quote);
        $this->dispatchSuccessMessage(__('Successfully removed coupon: %1', $this->couponCode));
        $this->reset(['couponCode', 'isCouponApplied']);
    }

    /**
     * @return bool
     */
    public function isCouponCodeValid(): bool
    {
        /** @var CouponInterface $coupon */
        $coupon = $this->couponFactory->create();
        $coupon->loadByCode($this->couponCode);

        return $coupon->getId()
            && $coupon->getCode()
            && strlen($this->couponCode) <= Cart::COUPON_CODE_MAX_LENGTH;
    }
}
