<?php namespace Meanbee\VipMembership\Cron;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;

class CheckVipMemberships
{
    /** @var \Meanbee\VipMembership\Helper\Config */
    protected $_configHelper;

    /** @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory */
    protected $_customerCollectionFactory;

    /** @var \Magento\Customer\Api\GroupManagementInterface */
    protected $_groupManagement;

    /** @var \Magento\Framework\Event\Manager */
    protected $_eventManager;

    /** @var \Meanbee\VipMembership\Model\VipCustomerManagement */
    protected $_vipCustomerManagement;

    public function __construct(
        \Meanbee\VipMembership\Helper\Config $configHelper,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        \Magento\Customer\Api\GroupManagementInterface $groupManagement,
        \Magento\Framework\Event\Manager $eventManager,
        \Meanbee\VipMembership\Model\VipCustomerManagement $vipCustomerManagement
    )
    {
        $this->_configHelper = $configHelper;
        $this->_customerCollectionFactory = $customerCollectionFactory;
        $this->_groupManagement = $groupManagement;
        $this->_eventManager = $eventManager;
        $this->_vipCustomerManagement = $vipCustomerManagement;
    }

    /**
     *
     * @return void
     */
    public function execute()
    {
        // Check that the module functionality is enabled.
        if (!$this->_configHelper->isEnabled()) {
            return;
        }

        /** @var \Magento\Customer\Model\ResourceModel\Customer\Collection $customerCollection */
        $customerCollection = $this->_customerCollectionFactory->create();
        $customerCollection->addFieldToFilter(CustomerInterface::GROUP_ID, ['eq' => $this->_configHelper->getVipCustomerGroup()])
            ->addFieldToFilter('vip_expiry_date', ['lteq' => (new \DateTime('now'))]);

        /** @var Customer $customer */
        foreach ($customerCollection as $customer) {
            $this->_vipCustomerManagement->revokeVipMembership($customer->getDataModel());
        }

        // Could be used for dispatching emails to inform the customer of their expired membership.
        $this->_eventManager->dispatch('meanbee_vipmembership_expired_customers', ['customers' => $customerCollection]);
    }
}
