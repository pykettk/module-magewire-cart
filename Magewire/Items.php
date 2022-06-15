<?php
/**
 * Copyright © element119. All rights reserved.
 * See LICENCE.txt for licence details.
 */
declare(strict_types=1);

namespace Element119\MagewireCart\Magewire;

use Element119\MagewireCart\Service\Cart;
use Magewirephp\Magewire\Component;

class Items extends Component
{
    /** @var Cart */
    private Cart $cartService;

    /** Magewire Component Properties */
    public array $items = [];

    /**
     * @param Cart $cartService
     */
    public function __construct(
        Cart $cartService
    ) {
        $this->cartService = $cartService;
    }

    /**
     * Component initial state setup.
     *
     * @return void
     */
    public function mount(): void
    {
        foreach ($this->cartService->getQuote()->getItems() as $cartItem) {
            $this->items[(int)$cartItem->getItemId()] = [
                'qty' => (float)$cartItem->getQty(),
                'interval' => $cartItem->getIsQtyDecimal() ? 0.1 : 1.0,
            ];
        }
    }

    /**
     * Update quote item quantity according to item ID, flagging whether to increment or decrement.
     *
     * @param int $itemId
     * @param bool $increment
     * @return void
     */
    public function updateQty(int $itemId, bool $increment): void
    {
        if (!$this->items[$itemId] || !($quote = $this->cartService->getQuote())) {
            return;
        }

        $oldQty = $this->items[$itemId]['qty'];
        $newQty = $increment ? $this->incrementQty($itemId) : $this->decrementQty($itemId);

        if ($newQty >= $this->items[$itemId]['interval'] && $oldQty !== $newQty) {
            $quote->getItemById($itemId)->setQty($newQty);
            $this->cartService->saveQuote($quote);
            $this->items[$itemId]['qty'] = $newQty;
            $this->emit('updateQty');
        }
    }

    /**
     * Remove quote item from cart by item ID.
     *
     * @param int $itemId
     * @return void
     */
    public function removeFromCart(int $itemId): void
    {
        if (!($quote = $this->cartService->getQuote())) {
            return;
        }

        $quote->removeItem($itemId);
        $this->cartService->saveQuote($quote);
        $this->emit('cartItemRemoved');
        unset($this->items[$itemId]);

        if (!$this->items) {
            $this->switchTemplate('Magento_Checkout::cart/noItems.phtml');
        }
    }

    /**
     * @param int $itemId
     * @return float
     */
    private function incrementQty(int $itemId): float
    {
        return (float)($this->items[$itemId]['qty'] + $this->items[$itemId]['interval']);
    }

    /**
     * @param int $itemId
     * @return float
     */
    private function decrementQty(int $itemId): float
    {
        return (float)($this->items[$itemId]['qty'] - $this->items[$itemId]['interval']);
    }
}
