<?php namespace Meanbee\VipMembership\Model\Product\Type;

use Magento\Catalog\Api\ProductRepositoryInterface;

class VipMembership extends \Magento\Catalog\Model\Product\Type\AbstractType
{
    const TYPE_CODE = 'vip';

    /** @var  \Magento\Customer\Model\Session */
    protected $customerSession;

    /** @var \Meanbee\VipMembership\Helper\Config */
    protected $configHelper;

    public function __construct(
        \Magento\Catalog\Model\Product\Option $catalogProductOption,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Catalog\Model\Product\Type $catalogProductType,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDb,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Registry $coreRegistry,
        \Psr\Log\LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Meanbee\VipMembership\Helper\Config $configHelper)
    {
        $this->configHelper = $configHelper;
        $this->customerSession = $customerSession;
        parent::__construct($catalogProductOption, $eavConfig, $catalogProductType, $eventManager, $fileStorageDb, $filesystem, $coreRegistry, $logger, $productRepository);
    }

    /**
     * Check is virtual product
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    public function isVirtual($product)
    {
        return true;
    }

    /**
     * Check that product of this type has weight
     *
     * @return bool
     */
    public function hasWeight()
    {
        return false;
    }

    /**
     * Delete data specific for VipMembership product type
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return void
     */
    public function deleteTypeSpecificData(\Magento\Catalog\Model\Product $product)
    {
    }

    /**
     * Ensure the customer is logged in when attempting to purchase a VIP Membership.
     *
     * @param \Magento\Framework\DataObject $buyRequest
     * @param \Magento\Catalog\Model\Product $product
     * @param string $processMode
     * @return array|string
     */
    protected function _prepareProduct(\Magento\Framework\DataObject $buyRequest, $product, $processMode)
    {
        // Don't allow the customer to purchase if functionality is disabled.
        if (!$this->configHelper->isEnabled()) {
            // @TODO Make a translatable phrase...
            return 'VIP Membership is currently disabled.';
        }

        // Only logged in users can add to cart.
        if ($this->_isStrictProcessMode($processMode) && !$this->customerSession->isLoggedIn()) {
            // @TODO Make a translatable phrase...
            return "You need to be logged in to purchase a membership";
        }

        return parent::_prepareProduct($buyRequest, $product, $processMode);
    }
}
