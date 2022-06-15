<?php
/**
 * Copyright © element119. All rights reserved.
 * See LICENCE.txt for licence details.
 */
declare(strict_types=1);

namespace Element119\MagewireCart\ViewModel;

use Element119\MagewireCart\Service\Cart as CartService;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Coupon implements ArgumentInterface
{
    /** @var CartService */
    private CartService $cartService;

    /**
     * @param CartService $cartService
     */
    public function __construct(
        CartService $cartService
    ) {
        $this->cartService = $cartService;
    }

    /**
     * @return bool
     */
    public function hasCouponCode(): bool
    {
        return $this->cartService->hasCouponCode();
    }
}
