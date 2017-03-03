<?php
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var $product \Magento\Catalog\Model\Product */
$product = $objectManager->create('Magento\Catalog\Model\Product');
$product
    ->setId(1000)
    ->setTypeId(\Meanbee\VipMembership\Model\Product\Type\VipMembership::TYPE_CODE)
    ->setAttributeSetId(4)
    ->setWebsiteIds([1])
    ->setName('VIP Membership')
    ->setSku('vip-product')
    ->setPrice(10)
    ->setDescription('Description')
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setCategoryIds([2])
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
    ->setUrlKey('vip-product')
    ->save();
