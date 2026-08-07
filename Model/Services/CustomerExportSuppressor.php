<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Model\Services;

class CustomerExportSuppressor
{
    /**
     * @var int
     */
    private int $suppressionDepth = 0;

    /**
     * Run an operation without publishing customer exports.
     *
     * @param callable $operation
     * @return mixed
     */
    public function suppress(callable $operation): mixed
    {
        ++$this->suppressionDepth;

        try {
            return $operation();
        } finally {
            --$this->suppressionDepth;
        }
    }

    /**
     * Check whether customer exports are currently suppressed.
     */
    public function isSuppressed(): bool
    {
        return $this->suppressionDepth > 0;
    }
}
