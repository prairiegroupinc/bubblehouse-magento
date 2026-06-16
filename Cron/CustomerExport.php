<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Cron;

use BubbleHouse\Integration\Model\ExportData\Customer\ExportScope;
use BubbleHouse\Integration\Model\ExportData\Customer\InitialExport;

class CustomerExport
{
    public function __construct(
        private readonly InitialExport $initialExport,
        private readonly ExportScope $exportScopeResolver
    ) {
    }

    public function execute(): void
    {
        foreach ($this->exportScopeResolver->getStoreScopes() as $scope) {
            $this->initialExport->execute(false, $scope);
        }
    }
}
