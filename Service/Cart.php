<?php
/**
 * Copyright © element119. All rights reserved.
 * See LICENCE.txt for licence details.
 */
declare(strict_types=1);

namespace Element119\MagewireCart\Service;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;

class Cart
{
    /** @var CheckoutSession */
    private CheckoutSession $checkoutSession;

    /** @var CartRepositoryInterface */
    private CartRepositoryInterface $cartRepository;

    /**
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $cartRepository
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->cartRepository = $cartRepository;
    }

    /**
     * @return Quote|null
     */
    public function getQuote(): ?Quote
    {
        try {
            return $this->checkoutSession->getQuote();
        } catch (NoSuchEntityException | LocalizedException $e) {
            return null;
        }
    }

    /**
     * @param CartInterface $quote
     * @return void
     */
    public function saveQuote(CartInterface $quote): void
    {
        $this->cartRepository->save($quote);
    }

    /**
     * @param CartInterface $quote
     * @return void
     */
    public function deleteQuote(CartInterface $quote): void
    {
        $this->cartRepository->delete($quote);
    }

    /**
     * @return bool
     */
    public function hasCouponCode(): bool
    {
        return $this->getQuote() ? $this->getQuote()->hasCouponCode() : false;
    }
}
