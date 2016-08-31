<?php namespace Meanbee\VipMembership\Setup;

use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Customer;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Meanbee\VipMembership\Model\Product\Type\VipMembership;

class InstallData implements InstallDataInterface
{
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * Init
     *
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        //
        // Add price attributes to the VIP Membership product type.
        //
        $this->addAttributesToVipMembershipType($eavSetup);

        //
        // Create vip_length, and vip_length_unit attributes.
        //
        $eavSetup->addAttribute(Product::ENTITY, 'vip_length', [
            'type' => 'int',
            'label' => 'VIP Membership Length',
            'input' => 'text',
            'required' => false,
            'user_defined' => true,
            'searchable' => true,
            'filterable' => true,
            'comparable' => false,
            'visible_in_advanced_search' => true,
            'is_used_in_grid' => true,
            'is_visible_in_grid' => false,
            'is_filterable_in_grid' => true,
            'apply_to' => VipMembership::TYPE_CODE
        ]);
        $eavSetup->addAttribute(Product::ENTITY, 'vip_length_unit', [
            'type' => 'varchar',
            'label' => 'VIP Length Unit',
            'input' => 'select',
            'source' => 'Meanbee\VipMembership\Model\Product\Attribute\Source\VipLengthUnit',
            'sort_order' => 9,
            'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
            'searchable' => false,
            'used_in_product_listing' => false,
            'apply_to' => VipMembership::TYPE_CODE
        ]);

        //
        // Create customer attributes (vip_expiry, vip_order_id)
        //
        $eavSetup->addAttribute(Customer::ENTITY, 'vip_expiry', [
            'type' => 'static',
            'label' => 'VIP Membership Expiry',
            'input' => 'date',
            'frontend' => 'Magento\Eav\Model\Entity\Attribute\Frontend\Datetime',
            'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\Datetime',
            'required' => false,
            'visible' => true,
            'system' => false,
            'input_filter' => 'date',
            'validate_rules' => 'a:1:{s:16:"input_validation";s:4:"date";}',
            'position' => 200,
        ]);
        $eavSetup->addAttribute(Customer::ENTITY, 'vip_order_id', [
            // Needs to be an integer.
            'type' => 'static',
            'label' => 'VIP Membership Order ID',
            'input' => 'text',
            'required' => false,
            'visible' => true,
            'system' => false,
            'validate_rules' => 'a:1:{s:15:"max_text_length";i:255;}',
            'position' => 201,
        ]);

        $data = [
            [
                'form_code' => 'adminhtml_customer',
                'attribute_id' => $eavSetup->getAttribute(Customer::ENTITY, 'vip_expiry', 'attribute_id')
            ],
            [
                'form_code' => 'adminhtml_customer',
                'attribute_id' => $eavSetup->getAttribute(Customer::ENTITY, 'vip_order_id', 'attribute_id')
            ],
        ];

        $setup->getConnection()
            ->insertMultiple($setup->getTable('customer_form_attribute'), $data);
    }

    /**
     * @param $eavSetup EavSetup
     */
    public function addAttributesToVipMembershipType($eavSetup)
    {
        $attributes = [
            'minimal_price',
            'msrp',
            'msrp_display_actual_price_type',
            'price',
            'special_price',
            'special_from_date',
            'special_to_date',
        ];
        foreach ($attributes as $attributeCode) {
            $relatedProductTypes = explode(
                ',',
                $eavSetup->getAttribute(\Magento\Catalog\Model\Product::ENTITY, $attributeCode, 'apply_to')
            );
            if (!in_array(VipMembership::TYPE_CODE, $relatedProductTypes)) {
                $relatedProductTypes[] = VipMembership::TYPE_CODE;
                $eavSetup->updateAttribute(
                    \Magento\Catalog\Model\Product::ENTITY,
                    $attributeCode,
                    'apply_to',
                    implode(',', $relatedProductTypes)
                );
            }
        }
    }
}
