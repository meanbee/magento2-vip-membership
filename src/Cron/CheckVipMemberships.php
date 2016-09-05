<?php namespace Meanbee\VipMembership\Cron;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;

class CheckVipMemberships
{
    /** @var \Meanbee\VipMembership\Helper\Config */
    protected $_configHelper;
    
    /** @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory */
    protected $_customerCollectionFactory;

    /** @var \Magento\Customer\Api\GroupManagementInterface  */
    protected $_groupManagement;
    
    /** @var \Magento\Framework\Event\Manager  */
    protected $_eventManager;

    public function __construct(
        \Meanbee\VipMembership\Helper\Config $configHelper,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        \Magento\Customer\Api\GroupManagementInterface $groupManagement,
        \Magento\Framework\Event\Manager $eventManager
    )
    {
        $this->_configHelper = $configHelper;
        $this->_customerCollectionFactory = $customerCollectionFactory;
        $this->_groupManagement = $groupManagement;
        $this->_eventManager = $eventManager;
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
            ->addFieldToFilter('vip_expiry', ['lteq' => (new \DateTime('now'))]);

        /** @var Customer $customer */
        foreach($customerCollection as $customer) {
            // Customers could be from different stores so we need to check and load the default group each time.
            $defaultGroupId = $this->_groupManagement->getDefaultGroup($customer->getStoreId())->getId();
            $customer->setGroupId($defaultGroupId);
            $customer->setData(['vip_expiry' => null]);
            $customer->save();
        }

        // Could be used for dispatching emails to inform the customer of their expired membership.
        $this->_eventManager->dispatch('meanbee_vipmembership_expired_customers', ['customers' => $customerCollection]);
        
    }
}
