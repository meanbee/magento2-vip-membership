<?php
namespace Meanbee\VipMembership\Test\Unit\Model;

use Magento\Catalog\Test\Block\Adminhtml\Product\Edit\Section\Options\Type\DateTime;
use Magento\Customer\Model\Data\Customer;
use Meanbee\VipMembership\Model\Product\Type\VipMembership;
use Meanbee\VipMembership\Model\Product\Attribute\Source\VipLengthUnit;

class VipCustomerManagementTest extends \PHPUnit_Framework_TestCase
{
    /** Mock group id to use when mocking the vip customer group without creating one.. */
    const MOCK_GROUP_ID = 2;

    /** @var  \Magento\Sales\Model\Order */
    protected $order;

    /** @var  \Meanbee\VipMembership\Model\VipCustomerManagement */
    protected $vipCustomerManagement;

    protected $configHelperMock;

    protected $orderItemCollectionFactoryMock;

    protected $vip_item;

    protected $productRepositoryMock;

    protected $productMock;

    /** @var  Customer */
    protected $customer;

    protected function setUp()
    {
        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->orderItemCollectionFactoryMock = $this->getMock(
            'Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory',
            ['create'],
            [],
            '',
            false
        );

        $this->vip_item = $this->getMock(
            'Magento\Sales\Model\ResourceModel\Order\Item',
            ['getProductType', 'getProductId', 'getQtyOrdered'],
            [],
            '',
            false
        );
        $this->vip_item->method('getProductType')->willReturn(VipMembership::TYPE_CODE);
        $this->vip_item->method('getQtyOrdered')->willReturn(1);

        $collection = $this->getMock('Magento\Sales\Model\ResourceModel\Order\Item\Collection', [], [], '', false);
        $collection->expects($this->any())
            ->method('setOrderFilter')
            ->willReturnSelf();
        $collection->expects($this->any())
            ->method('getItems')
            ->willReturn([$this->vip_item]);
        $this->orderItemCollectionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($collection);

        $this->order = $helper->getObject(
            'Magento\Sales\Model\Order', [
            'orderItemCollectionFactory' => $this->orderItemCollectionFactoryMock,
            'data' => ['status' => \Magento\Sales\Model\Order::STATE_PROCESSING]
        ]);

        $this->productMock = $this->getMock('Magento\Catalog\Model\Product', ['getVipLength', 'getVipLengthUnit', 'getSku', 'getExtensionAttributes'], [], '', false);

        $extensionAttributes = $helper->getObject('Magento\Catalog\Api\Data\ProductExtension');
        $this->productMock->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $this->productMock->getExtensionAttributes()->setVipLength(1);
        $this->productMock->getExtensionAttributes()->setVipLengthUnit(VipLengthUnit::UNIT_DAYS);

        $this->productRepositoryMock = $this->getMock('Magento\Catalog\Model\ProductRepository', ['getById'], [], '', false);
        $this->productRepositoryMock->method('getById')->willReturn($this->productMock);

        $this->configHelperMock = $this->getMockBuilder('Meanbee\VipMembership\Helper\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configHelperMock->method('getOrderStatus')->willReturn(\Magento\Sales\Model\Order::STATE_PROCESSING);
        $this->configHelperMock->method('getVipCustomerGroup')->willReturn(self::MOCK_GROUP_ID);

        $defaultGroupMock = $this->getMockBuilder('\Magento\Customer\Api\Data\GroupInterface')->disableOriginalConstructor()->getMock();
        $defaultGroupMock->method('getId')->willReturn(1);
        $groupManagementMock = $this->getMockBuilder('\Magento\Customer\Api\GroupManagementInterface')->disableOriginalConstructor()->getMock();
        $groupManagementMock->method('getDefaultGroup')->willReturn($defaultGroupMock);
        $orderHelper = $helper->getObject('Meanbee\VipMembership\Helper\Order', [
            'productRepository' => $this->productRepositoryMock,
            'configHelper' => $this->configHelperMock]);

        $this->vipCustomerManagement = $helper->getObject('Meanbee\VipMembership\Model\VipCustomerManagement', [
            'orderHelper' => $orderHelper,
            'groupManagement' => $groupManagementMock,
            'productRepository' => $this->productRepositoryMock,
            'configHelper' => $this->configHelperMock]);
    }

    public function testRevokeVipStatus()
    {
        $original_customer = $this->getVipCustomer();
        $revoked_customer = $this->vipCustomerManagement->revokeVipMembership($original_customer);

        // Confirm expiry date has been updated.
        $this->assertEquals((new \DateTime('now'))->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT), $revoked_customer->getExtensionAttributes()->getVipExpiry());

        // Confirm customer group has been changed.
        $this->assertEquals(1, $revoked_customer->getGroupId());
    }

    /**
     * Testing when a customer becomes a VIP for the first time.
     */
    public function testBecomeVipMember()
    {
        $customer = $this->vipCustomerManagement->becomeVipMember($this->getNonVipCustomer(), $this->order);
        $expected_expiry = new \DateTime();
        $expected_expiry->add(new \DateInterval('P1D'));
        $this->assertEquals($expected_expiry->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT), $customer->getExtensionAttributes()->getVipExpiry());
        $expected_group = self::MOCK_GROUP_ID;
        $this->assertEquals($expected_group, $customer->getGroupId());
    }

    /**
     * Testing when a customer extends their membership.
     */
    public function testUpdateVipMembership()
    {

    }

    protected function getNonVipOrder()
    {
        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $orderItemCollectionFactoryMock = $this->getMock(
            'Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory',
            ['create'],
            [],
            '',
            false
        );

        $item = $this->getMock(
            'Magento\Sales\Model\ResourceModel\Order\Item',
            ['getProductType', 'getProductId'],
            [],
            '',
            false
        );
        $item->method('getProductType')->willReturn('simple');

        $collection = $this->getMock('Magento\Sales\Model\ResourceModel\Order\Item\Collection', [], [], '', false);
        $collection->expects($this->any())
            ->method('setOrderFilter')
            ->willReturnSelf();
        $collection->expects($this->any())
            ->method('getItems')
            ->willReturn([$item]);
        $orderItemCollectionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($collection);

        return $helper->getObject(
            'Magento\Sales\Model\Order', [
            'orderItemCollectionFactory' => $orderItemCollectionFactoryMock,
            'data' => ['status' => \Magento\Sales\Model\Order::STATE_CLOSED]
        ]);
    }

    protected function getVipCustomer()
    {
        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        /** @var \Magento\Customer\Model\Data\Customer $customer */
        $customer = $helper->getObject('Magento\Customer\Model\Data\Customer');

        $customer->setGroupId(self::MOCK_GROUP_ID);

        $extensionAttributes = $helper->getObject('Magento\Customer\Api\Data\CustomerExtension');
        $customer->setExtensionAttributes($extensionAttributes);

        $expiry = new \DateTime();
        $expiry->add(new \DateInterval('P1D'));

        $customer->getExtensionAttributes()->setVipExpiry($expiry);

        $this->assertEquals(2, $customer->getGroupId());

        return $customer;
    }

    protected function getNonVipCustomer()
    {
        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        /** @var \Magento\Customer\Model\Data\Customer $customer */
        $customer = $helper->getObject('Magento\Customer\Model\Data\Customer');

        $extensionAttributes = $helper->getObject('Magento\Customer\Api\Data\CustomerExtension');
        $customer->setExtensionAttributes($extensionAttributes);

        return $customer;
    }
}
