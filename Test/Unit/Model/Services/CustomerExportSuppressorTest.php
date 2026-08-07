<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Test\Unit\Model\Services;

use BubbleHouse\Integration\Model\Services\CustomerExportSuppressor;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class CustomerExportSuppressorTest extends TestCase
{
    public function testSuppressionIsResetAfterFailure(): void
    {
        $suppressor = new CustomerExportSuppressor();

        try {
            $this->failDuringSuppression($suppressor);
            self::fail('Expected the operation to fail.');
        } catch (RuntimeException $exception) {
            self::assertSame('Save failed.', $exception->getMessage());
        }

        self::assertFalse($suppressor->isSuppressed());
    }

    private function failDuringSuppression(CustomerExportSuppressor $suppressor): void
    {
        $suppressor->suppress(static function () use ($suppressor): void {
            self::assertTrue($suppressor->isSuppressed());
            throw new RuntimeException('Save failed.');
        });
    }
}
