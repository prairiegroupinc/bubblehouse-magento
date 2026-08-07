<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class CreateCustomerRewardsAttributes implements DataPatchInterface
{
    public const TIER_ATTRIBUTE_CODE = 'bh_tier';
    public const TIER_MAX_LENGTH = 255;
    public const POINTS_BALANCE_ATTRIBUTE_CODE = 'bh_points_balance';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly CustomerSetupFactory $customerSetupFactory
    ) {
    }

    /**
     * Create the BubbleHouse customer rewards attributes.
     *
     * @return $this
     */
    public function apply(): self
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $entityType = $customerSetup->getEavConfig()->getEntityType(Customer::ENTITY);
        $attributeSetId = (int) $entityType->getDefaultAttributeSetId();
        $attributeGroupId = (int) $customerSetup->getDefaultAttributeGroupId(
            Customer::ENTITY,
            $attributeSetId
        );

        $attributes = [
            self::TIER_ATTRIBUTE_CODE => [
                'type' => 'varchar',
                'label' => 'BubbleHouse Tier',
                'input' => 'text',
                'validate_rules' => ['max_text_length' => self::TIER_MAX_LENGTH],
                'position' => 1002,
            ],
            self::POINTS_BALANCE_ATTRIBUTE_CODE => [
                'type' => 'decimal',
                'label' => 'BubbleHouse Points Balance',
                'input' => 'text',
                'position' => 1003,
            ],
        ];

        foreach ($attributes as $attributeCode => $attributeData) {
            $customerSetup->addAttribute(
                Customer::ENTITY,
                $attributeCode,
                $attributeData + [
                    'required' => false,
                    'visible' => true,
                    'user_defined' => true,
                    'system' => false,
                ]
            );

            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeCode);
            $attribute->addData([
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId,
                'used_in_forms' => ['adminhtml_customer'],
            ]);
            $attribute->save();
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * Get patch dependencies.
     *
     * @return array<class-string>
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Get patch aliases.
     *
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
