<?php
/**
 * Copyright © element119. All rights reserved.
 * See LICENCE.txt for licence details.
 */
declare(strict_types=1);

namespace Element119\MagewireCart\Magewire\Action;

use Element119\MagewireCart\Service\Cart as CartService;
use Magewirephp\Magewire\Component;

class Clear extends Component
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
     * @return void
     */
    public function execute(): void
    {
        if ($quote = $this->cartService->getQuote()) {
            $this->cartService->deleteQuote($quote);
            $this->redirect('/checkout/cart');
        }
    }
}
