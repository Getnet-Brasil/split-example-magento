<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\SplitExampleMagento\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class Sub Seller Id - add attribute for product.
 */
class SubSellerId implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    protected $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    protected $eavSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory          $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function apply()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $productTypes = [
            \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,
            \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL,
            \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE,
            \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE,
            \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE,
        ];
        $productTypes = join(',', $productTypes);

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'getnet_sub_seller_id',
            [
                'type'                    => 'varchar',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'Getnet Sub Seller',
                'input'                   => 'hidden',
                'class'                   => '',
                'source'                  => '',
                'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'default'                 => '',
                'searchable'              => false,
                'filterable'              => false,
                'comparable'              => false,
                'visible_on_front'        => false,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => $productTypes,
            ]
        );

        $entityTypeId = $eavSetup->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY);
        $attributeSetIds = $eavSetup->getAllAttributeSetIds($entityTypeId);
        foreach ($attributeSetIds as $attributeSetId) {
            $groupId = $eavSetup->getAttributeGroupId($entityTypeId, $attributeSetId, 'General');
            $eavSetup->addAttributeToGroup(
                $entityTypeId,
                $attributeSetId,
                $groupId,
                'getnet_sub_seller_id',
                null
            );
        }
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getVersion()
    {
        return '100.0.1';
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
