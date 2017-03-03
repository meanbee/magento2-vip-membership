<?php
namespace Meanbee\VipMembership\Test\Integration\Controller;

use Magento\Customer\Model\Session;


class VipProductTest extends \Magento\TestFramework\TestCase\AbstractController
{
    public static function loadProductFixture()
    {
        include __DIR__ . '/../_files/products.php';
    }

    /**
     * Make sure the VIP Membership product can be viewed.
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture loadProductFixture
     */
    public function testProductAppears()
    {
        $this->dispatch('catalog/product/view/id/1000');
        $this->assertContains('VIP Membership', $this->getResponse()->getBody());
    }
}
