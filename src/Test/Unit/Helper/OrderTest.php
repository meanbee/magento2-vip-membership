<?php namespace Meanbee\VipMembership\Test\Unit\Helper;

use Meanbee\VipMembership\Model\Product\Attribute\Source\VipLengthUnit;
use Meanbee\VipMembership\Model\Product\Type\VipMembership;

class OrderTest extends \PHPUnit_Framework_TestCase
{
    /** @var  \Magento\Sales\Model\Order */
    protected $order;

    /** @var  \Meanbee\VipMembership\Helper\Order */
    protected $orderHelper;

    protected $configHelperMock;

    protected $orderItemCollectionFactoryMock;

    protected $vip_item;

    protected $productRepositoryMock;

    protected $productMock;

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

        $this->productRepositoryMock = $this->getMock('Magento\Catalog\Model\ProductRepository', ['getById'], [], '', false);
        $this->productRepositoryMock->method('getById')->willReturn($this->productMock);

        $this->configHelperMock = $this->getMockBuilder('Meanbee\VipMembership\Helper\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configHelperMock->method('getOrderStatus')->willReturn(\Magento\Sales\Model\Order::STATE_PROCESSING);
        $this->orderHelper = $helper->getObject('Meanbee\VipMembership\Helper\Order', [
            'productRepository' => $this->productRepositoryMock,
            'configHelper' => $this->configHelperMock]);
    }

    public function testCanOrderBecomeVip()
    {
        $this->assertTrue($this->orderHelper->canOrderBecomeVip($this->order));
    }

    public function testOrderCannotBecomeVip()
    {
        $this->assertFalse($this->orderHelper->canOrderBecomeVip($this->getNonVipOrder()));
    }

    public function testHasVipProductBeenPurchased()
    {
        $this->assertTrue($this->orderHelper->hasVipProductBeenPurchased($this->order));
    }

    public function testNoVipProductPurchased()
    {
        $nonVipOrder = $this->getNonVipOrder();
        $this->assertFalse($this->orderHelper->hasVipProductBeenPurchased($nonVipOrder));
    }

    public function testGetPurchasedMembershipLength()
    {
        // Expect the length of the membership to be +1 day
        $this->productMock->getExtensionAttributes()->setVipLength(1);
        $this->productMock->getExtensionAttributes()->setVipLengthUnit(VipLengthUnit::UNIT_DAYS);

        // P1D = plus 1 day
        $expected_length = new \DateInterval('P1D');
        $actual_length = $this->orderHelper->getPurchasedMembershipLength($this->order);
        $this->assertEquals($expected_length, $actual_length);
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
}
