<?php
/**
 * Copyright © element119. All rights reserved.
 * See LICENCE.txt for licence details.
 */
declare(strict_types=1);

namespace Element119\MagewireCart\Magewire\Action;

use Magewirephp\Magewire\Component;

class Update extends Component
{
    /**
     * @return void
     */
    public function execute(): void
    {
        $this->emit('updateCart');
    }
}
