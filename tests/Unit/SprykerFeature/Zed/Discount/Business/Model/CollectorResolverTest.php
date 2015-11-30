<?php
/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace Unit\SprykerFeature\Zed\Discount\Business\Model;

use Codeception\TestCase\Test;
use Generated\Shared\Transfer\CartTransfer;
use Generated\Shared\Transfer\DiscountCollectorTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use SprykerFeature\Zed\Discount\Business\Model\CollectorResolver;
use SprykerFeature\Zed\Discount\Dependency\Plugin\DiscountCollectorPluginInterface;
use SprykerFeature\Zed\Discount\DiscountConfigInterface;
use SprykerFeature\Zed\Cart\Business\Model\CalculableContainer;
use Generated\Shared\Transfer\DiscountTransfer;

class CollectorResolverTest extends Test
{

    const COLLECTOR_1 = 'COLLECTOR_1';
    const COLLECTOR_2 = 'COLLECTOR_2';

    public function testWhenANDConditionUsedWithCollectorsProvidingDifferentItemsThenNoItemsReturned()
    {
        $calculableContainer = $this->createCalculableContainer();

        $collectorPickedItem1 = $calculableContainer->getCalculableObject()->getItems()[0];
        $collectorPickedItem2 = $calculableContainer->getCalculableObject()->getItems()[2];

        $collectors = [];
        $collectors[self::COLLECTOR_1] = $this->createCollectorPluginMock([$collectorPickedItem1]);
        $collectors[self::COLLECTOR_2] = $this->createCollectorPluginMock([$collectorPickedItem2]);

        $collectorConfig = $this->getDiscountCollectorConfigurator($collectors);
        $collectorResolver = $this->createCollectorResolver($collectorConfig);

        $discountTransfer = $this->createDiscountTransfer();
        $discountTransfer->setCollectorLogicalOperator(CollectorResolver::OPERATOR_AND);

        $collectedItems = $collectorResolver->collectItems($calculableContainer, $discountTransfer);

        $this->assertCount(0, $collectedItems);
    }

    public function testWhenANDConditionUsedWithCollectorsProvidingSameItemsThenMatchedItemReturned()
    {
        $calculableContainer = $this->createCalculableContainer();

        $collectorPickedItem1 = $calculableContainer->getCalculableObject()->getItems()[0];

        $collectors = [];
        $collectors[self::COLLECTOR_1] = $this->createCollectorPluginMock([$collectorPickedItem1]);
        $collectors[self::COLLECTOR_2] = $this->createCollectorPluginMock([$collectorPickedItem1]);

        $collectorConfig = $this->getDiscountCollectorConfigurator($collectors);
        $collectorResolver = $this->createCollectorResolver($collectorConfig);

        $discountTransfer = $this->createDiscountTransfer();
        $discountTransfer->setCollectorLogicalOperator(CollectorResolver::OPERATOR_AND);

        $collectedItems = $collectorResolver->collectItems($calculableContainer, $discountTransfer);

        $this->assertCount(1, $collectedItems);
    }

    public function testWhenORConditionUsedWithDifferentItemsThenItShouldReturnAllCollectorItems()
    {
        $calculableContainer = $this->createCalculableContainer();

        $collectorPickedItem1 = $calculableContainer->getCalculableObject()->getItems()[0];
        $collectorPickedItem2 = $calculableContainer->getCalculableObject()->getItems()[2];

        $collectors = [];
        $collectors[self::COLLECTOR_1] = $this->createCollectorPluginMock([$collectorPickedItem1]);
        $collectors[self::COLLECTOR_2] = $this->createCollectorPluginMock([$collectorPickedItem2]);

        $collectorConfig = $this->getDiscountCollectorConfigurator($collectors);
        $collectorResolver = $this->createCollectorResolver($collectorConfig);

        $discountTransfer = $this->createDiscountTransfer();
        $discountTransfer->setCollectorLogicalOperator(CollectorResolver::OPERATOR_OR);

        $collectedItems = $collectorResolver->collectItems($calculableContainer, $discountTransfer);

        $this->assertCount(2, $collectedItems);
    }

    public function testWhenFirstCollectorEmptyAndANDConditionUsedShouldBeNoItemsCollected()
    {
        $calculableContainer = $this->createCalculableContainer();

        $collectors = [];
        $collectors[self::COLLECTOR_1] = $this->createCollectorPluginMock([]);

        $collectorConfig = $this->getDiscountCollectorConfigurator($collectors);
        $collectorResolver = $this->createCollectorResolver($collectorConfig);

        $discountTransfer = $this->createDiscountTransfer();
        $discountTransfer->setCollectorLogicalOperator(CollectorResolver::OPERATOR_AND);

        $collectedItems = $collectorResolver->collectItems($calculableContainer, $discountTransfer);

        $this->assertCount(0, $collectedItems);
    }

    /**
     * @return DiscountTransfer
     */
    protected function createDiscountTransfer()
    {
        $discountTransfer = new DiscountTransfer();

        $discountCollectors = new \ArrayObject();
        $discountCollectorTransfer = $this->createDiscountCollectorTransfer();
        $discountCollectorTransfer->setCollectorPlugin(self::COLLECTOR_1);
        $discountCollectors->append($discountCollectorTransfer);

        $discountCollectorTransfer = $this->createDiscountCollectorTransfer();
        $discountCollectorTransfer->setCollectorPlugin(self::COLLECTOR_2);
        $discountCollectors->append($discountCollectorTransfer);

        $discountTransfer->setDiscountCollectors($discountCollectors);

        return $discountTransfer;
    }

    /**
     * @return CalculableContainer
     */
    protected function createCalculableContainer()
    {
        $cartTransfer = $this->createCartTransfer();
        $cartTransfer->addItem($this->createItemTransfer('SKU-123'));
        $cartTransfer->addItem($this->createItemTransfer('SKU-321'));
        $cartTransfer->addItem($this->createItemTransfer('SKU-111'));
        $cartTransfer->addItem($this->createItemTransfer('SKU-222'));

        $calculableContainer = new CalculableContainer($cartTransfer);

        return $calculableContainer;
    }

    /**
     * @param array $collectedItems
     *
     * @return DiscountCollectorPluginInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createCollectorPluginMock(array $collectedItems)
    {
        $collectorPluginMock = $this
            ->getMockBuilder('\SprykerFeature\Zed\Discount\Dependency\Plugin\DiscountCollectorPluginInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $collectorPluginMock->method('collect')->willReturn($collectedItems);

        return $collectorPluginMock;
    }

    /**
     * @param array $collectorPlugins
     *
     * @return DiscountConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getDiscountCollectorConfigurator(array $collectorPlugins)
    {
        $discountConfigMock = $this
            ->getMockBuilder('\SprykerFeature\Zed\Discount\DiscountConfigInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $i = 0;
        foreach ($collectorPlugins as $idCollector => $collector) {
            $discountConfigMock
                ->expects($this->at($i++))
                ->method('getCollectorPluginByName')
                ->with($this->equalTo($idCollector))
                ->willReturn($collector);
        }

        return $discountConfigMock;
    }

    /**
     * @param string $sku
     *
     * @return ItemTransfer
     */
    protected function createItemTransfer($sku)
    {
        $itemTransfer = new ItemTransfer();
        $itemTransfer->setSku($sku);

        return $itemTransfer;
    }

    /**
     * @return CartTransfer
     */
    protected function createCartTransfer()
    {
        return new CartTransfer();
    }

    /**
     * @return DiscountCollectorTransfer
     */
    protected function createDiscountCollectorTransfer()
    {
        return new DiscountCollectorTransfer();
    }

    /**
     * @param $collectorConfig
     *
     * @return CollectorResolver
     */
    protected function createCollectorResolver($collectorConfig)
    {
        return new CollectorResolver($collectorConfig);
    }

}
