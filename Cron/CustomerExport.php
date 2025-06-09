<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Cron;

use BubbleHouse\Integration\Model\EportData\Customer\InitialExport;

class CustomerExport
{
    public function __construct(
        private readonly InitialExport $initialExport
    ) {
    }

    public function execute(): void
    {
        $this->initialExport->execute();
    }
}
