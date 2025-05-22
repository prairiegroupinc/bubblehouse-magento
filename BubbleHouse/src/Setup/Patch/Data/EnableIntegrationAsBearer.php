<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class EnableIntegrationAsBearer implements DataPatchInterface
{
    private const CONFIG_PATH = 'oauth/consumer/enable_integration_as_bearer';

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly WriterInterface $configWriter
    ) {
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function apply(): self
    {
        $this->moduleDataSetup->startSetup();
        $this->configWriter->save(
            self::CONFIG_PATH,
            1,
            \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );

        $this->moduleDataSetup->endSetup();

        return $this;
    }
}
