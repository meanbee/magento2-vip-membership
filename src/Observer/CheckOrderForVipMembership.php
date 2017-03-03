<?php namespace Meanbee\VipMembership\Observer;

use \Magento\Framework\Event\ObserverInterface,
    \Magento\Sales\Model\ResourceModel\Order\CollectionFactory,
    \Meanbee\VipMembership\Helper\Config,
    \Meanbee\VipMembership\Helper\Order,
    \Meanbee\VipMembership\Model\VipCustomerManagement,
    \Magento\Customer\Model\Session;

class CheckOrderForVipMembership implements ObserverInterface
{
    /** @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory */
    protected $_ordersCollectionFactory;

    /** @var \Meanbee\VipMembership\Helper\Config */
    protected $_configHelper;

    /** @var \Meanbee\VipMembership\Helper\Order */
    protected $_orderHelper;

    /** @var \Meanbee\VipMembership\Model\VipCustomerManagement */
    protected $_vipCustomerManager;

    /** @var \Magento\Customer\Model\Session */
    protected $_customerSession;

    public function __construct(
        CollectionFactory $ordersCollectionFactory,
        Config $configHelper,
        Order $orderHelper,
        VipCustomerManagement $vipCustomerManagement,
        Session $customerSession)
    {
        $this->_ordersCollectionFactory = $ordersCollectionFactory;
        $this->_configHelper = $configHelper;
        $this->_orderHelper = $orderHelper;
        $this->_vipCustomerManager = $vipCustomerManagement;
        $this->_customerSession = $customerSession;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        // Don't proceed if VIP Membership functionality is disabled.

        if (!$this->_configHelper->isEnabled()) {
            return;
        }
        
        $orderIds = $observer->getData('order_ids');

        /** @var /Magento/Sales/Model/ResourceModel/Order/Collection $orderCollection */
        $orderCollection = $this->_ordersCollectionFactory->create();
        $orderCollection->addFieldToFilter('entity_id', ['in' => $orderIds]);
        
        /** @var /Magento/Sales/Model/Order $order */
        foreach ($orderCollection as $order) {
            if (!$this->_orderHelper->canOrderBecomeVip($order)) {
                continue;
            }

            if (!$this->_orderHelper->hasVipProductBeenPurchased($order)) {
                continue;
            }

            $this->_vipCustomerManager->becomeVipMember($this->_customerSession->getCustomerData(), $order);
        }
    }
}
