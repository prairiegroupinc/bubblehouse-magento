<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

class CreateCustomerExportAttribute implements DataPatchInterface
{
    public const ATTRIBUTE_CODE = 'bh_exported';

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly CustomerSetupFactory $customerSetupFactory
    ) {
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $entityType = $customerSetup->getEavConfig()->getEntityType(Customer::ENTITY);
        $attributeSetId = $entityType->getDefaultAttributeSetId();
        $attributeGroupId = $customerSetup->getDefaultAttributeGroupId(Customer::ENTITY, $attributeSetId);

        $customerSetup->addAttribute(Customer::ENTITY, self::ATTRIBUTE_CODE, [
            'type' => 'int',
            'label' => 'BubbleHouse Initial Export',
            'input' => 'boolean',
            'required' => false,
            'visible' => true,
            'user_defined' => true,
            'position' => 1000,
            'system' => 0,
            'default' => 0,
            'backend' => '',
            'frontend' => '',
        ]);

        $attribute = $customerSetup->getEavConfig()
            ->getAttribute(Customer::ENTITY, self::ATTRIBUTE_CODE);
        $attribute->addData([
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            'used_in_forms' => [],
        ]);
        $attribute->save();
        $attribute = $customerSetup->getEavConfig()->getAttribute(
            Customer::ENTITY,
            self::ATTRIBUTE_CODE
        );
        $attributeId = (int)$attribute->getId();

        $connection = $this->moduleDataSetup->getConnection();

        $customerIds = $connection->fetchCol(
            "SELECT entity_id FROM " . $connection->getTableName('customer_entity')
        );

        $data = [];
        foreach ($customerIds as $customerId) {
            $data[] = [
                'attribute_id'   => $attributeId,
                'entity_id'      => $customerId,
                'value'          => 0,
            ];
        }

        if (!empty($data)) {
            $connection->insertOnDuplicate(
                $connection->getTableName('customer_entity_int'),
                $data,
                ['value']
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
