<?php namespace Meanbee\VipMembership\Cron;
use \Magento\Framework\Event\ObserverInterface,
    \Meanbee\VipMembership\Helper\Config;

class EmailExpiredVipMembers implements ObserverInterface
{

    /** @var \Meanbee\VipMembership\Helper\Config */
    protected $_configHelper;

    /**
     * EmailExpiredVipMembers constructor.
     */
    public function __construct(Config $configHelper)
    {
        $this->_configHelper = $configHelper;
    }

    /**
     * Email Expired Vip Members
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->_configHelper->isEnabled()) {
            return;
        }
        /** @var \Magento\Customer\Model\ResourceModel\Customer\Collection $customers */
        $customers = $observer->getData('customers');

        // Nothing to do.
        if (!$customers || $customers->getSize() == 0) {
            return;
        }

        // TODO Implement email.......

    }
}
