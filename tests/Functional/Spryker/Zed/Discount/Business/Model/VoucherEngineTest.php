<?php

/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace Functional\Spryker\Zed\Discount\Business\Model;

use Codeception\TestCase\Test;
use Generated\Shared\Transfer\DiscountCollectorTransfer;
use Generated\Shared\Transfer\DiscountTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Spryker\Zed\Discount\Business\Distributor\Distributor;
use Spryker\Zed\Discount\Business\Model\Calculator;
use Spryker\Zed\Discount\Business\Model\CollectorResolver;
use Spryker\Zed\Discount\DiscountConfig;
use Orm\Zed\Discount\Persistence\SpyDiscount;
use Orm\Zed\Discount\Persistence\SpyDiscountCollector;
use Spryker\Zed\Messenger\Business\MessengerFacade;
use Spryker\Zed\Sales\Business\Model\CalculableContainer;

/**
 * @group VoucherEngineTest
 * @group Discount
 */
class VoucherEngineTest extends Test
{

    const ITEM_GROSS_PRICE_500 = 500;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
    }

    /**
     * @return void
     */
    public function testCalculationWithoutAnyDiscountShouldNotReturnMatchingDiscounts()
    {
        $settings = new DiscountConfig();
        $calculator = $this->getCalculator();

        $order = $this->getOrderWithTwoItems();

        $result = $calculator->calculate([], $order, $settings, new Distributor());

        $this->assertEquals(0, count($result));
    }

    /**
     * @return void
     */
    public function testOneDiscountShouldNotBeFilteredOut()
    {
        $discount = $this->initializeDiscount(
            'name 1',
            DiscountConfig::PLUGIN_CALCULATOR_PERCENTAGE,
            50,
            true,
            DiscountConfig::PLUGIN_COLLECTOR_ITEM,
            true
        );

        $settings = new DiscountConfig();
        $calculator = $this->getCalculator();

        $order = $this->getOrderWithTwoItems();

        $result = $calculator->calculate([$discount], $order, $settings, new Distributor());

        $this->assertEquals(1, count($result));
    }

    /**
     * @return void
     */
    public function testTwoDiscountsShouldNotBeFilteredOut()
    {
        $discount1 = $this->initializeDiscount(
            'name 1',
            DiscountConfig::PLUGIN_CALCULATOR_PERCENTAGE,
            50,
            true,
            DiscountConfig::PLUGIN_COLLECTOR_ITEM,
            true
        );

        $discount2 = $this->initializeDiscount(
            'name 2',
            DiscountConfig::PLUGIN_CALCULATOR_PERCENTAGE,
            50,
            true,
            DiscountConfig::PLUGIN_COLLECTOR_ITEM,
            false
        );

        $settings = new DiscountConfig();
        $calculator = $this->getCalculator();

        $order = $this->getOrderWithTwoItems();
        $result = $calculator->calculate([$discount1, $discount2], $order, $settings, new Distributor());
        $this->assertEquals(2, count($result));
    }

    /**
     * @return void
     */
    public function testFilterOutLowestUnprivilegedDiscountIfThereAreMoreThanOne()
    {
        $discount1 = $this->initializeDiscount(
            'name 1',
            DiscountConfig::PLUGIN_CALCULATOR_PERCENTAGE,
            50,
            true,
            DiscountConfig::PLUGIN_COLLECTOR_ITEM,
            true
        );

        $discount2 = $this->initializeDiscount(
            'name 2',
            DiscountConfig::PLUGIN_CALCULATOR_PERCENTAGE,
            50,
            true,
            DiscountConfig::PLUGIN_COLLECTOR_ITEM,
            false
        );

        $discount3 = $this->initializeDiscount(
            'name 3',
            DiscountConfig::PLUGIN_CALCULATOR_PERCENTAGE,
            60,
            true,
            DiscountConfig::PLUGIN_COLLECTOR_ITEM,
            false
        );

        $settings = new DiscountConfig();
        $calculator = $this->getCalculator();

        $order = $this->getOrderWithTwoItems();
        $result = $calculator->calculate(
            [$discount1, $discount2, $discount3], $order, $settings, new Distributor()
        );
        $this->assertEquals(2, count($result));
    }

    /**
     * @return void
     */
    public function testFilterOutLowestUnprivilegedDiscountIfThereAreMoreThanTwo()
    {
        $discount1 = $this->initializeDiscount(
            'name 1',
            DiscountConfig::PLUGIN_CALCULATOR_PERCENTAGE,
            50,
            true,
            DiscountConfig::PLUGIN_COLLECTOR_ITEM,
            true
        );

        $discount2 = $this->initializeDiscount(
            'name 2',
            DiscountConfig::PLUGIN_CALCULATOR_PERCENTAGE,
            50,
            true,
            DiscountConfig::PLUGIN_COLLECTOR_ITEM,
            false
        );

        $discount3 = $this->initializeDiscount(
            'name 3',
            DiscountConfig::PLUGIN_CALCULATOR_PERCENTAGE,
            60,
            true,
            DiscountConfig::PLUGIN_COLLECTOR_ITEM,
            false
        );

        $discount4 = $this->initializeDiscount(
            'name 4',
            DiscountConfig::PLUGIN_CALCULATOR_PERCENTAGE,
            70,
            true,
            DiscountConfig::PLUGIN_COLLECTOR_ITEM,
            false
        );

        $settings = new DiscountConfig();
        $calculator = $this->getCalculator();

        $order = $this->getOrderWithTwoItems();
        $result = $calculator->calculate([$discount1, $discount2, $discount3, $discount4], $order, $settings, new Distributor());
        $this->assertEquals(2, count($result));
    }

    /**
     * @return void
     */
    public function testFilterOutLowestUnprivilegedDiscountIfThereAreMoreThanTwoAndTwoPrivilegedOnes()
    {
        $discount1 = $this->initializeDiscount(
            'name 1',
            DiscountConfig::PLUGIN_CALCULATOR_PERCENTAGE,
            50,
            true,
            DiscountConfig::PLUGIN_COLLECTOR_ITEM,
            true
        );

        $discount2 = $this->initializeDiscount(
            'name 2',
            DiscountConfig::PLUGIN_CALCULATOR_PERCENTAGE,
            50,
            true,
            DiscountConfig::PLUGIN_COLLECTOR_ITEM,
            false
        );

        $discount3 = $this->initializeDiscount(
            'name 3',
            DiscountConfig::PLUGIN_CALCULATOR_PERCENTAGE,
            60,
            true,
            DiscountConfig::PLUGIN_COLLECTOR_ITEM,
            false
        );

        $discount4 = $this->initializeDiscount(
            'name 4',
            DiscountConfig::PLUGIN_CALCULATOR_PERCENTAGE,
            70,
            true,
            DiscountConfig::PLUGIN_COLLECTOR_ITEM,
            false
        );

        $discount5 = $this->initializeDiscount(
            'name 5',
            DiscountConfig::PLUGIN_CALCULATOR_PERCENTAGE,
            80,
            true,
            DiscountConfig::PLUGIN_COLLECTOR_ITEM,
            true
        );

        $settings = new DiscountConfig();
        $calculator = $this->getCalculator();

        $order = $this->getOrderWithTwoItems();
        $result = $calculator->calculate([$discount1, $discount2, $discount3, $discount4, $discount5], $order, $settings, new Distributor());
        $this->assertEquals(3, count($result));
    }

    /**
     * @param $displayName
     * @param $calculatorPlugin
     * @param $amount
     * @param $isActive
     * @param $collectorPlugin
     * @param bool $isPrivileged
     *
     * @return \Orm\Zed\Discount\Persistence\SpyDiscount
     */
    protected function initializeDiscount(
        $displayName,
        $calculatorPlugin,
        $amount,
        $isActive,
        $collectorPlugin,
        $isPrivileged = true
    ) {
        $discountEntity = new SpyDiscount();
        $discountEntity->setDisplayName($displayName);
        $discountEntity->setAmount($amount);
        $discountEntity->setIsActive($isActive);
        $discountEntity->setCalculatorPlugin($calculatorPlugin);
        $discountEntity->setIsPrivileged($isPrivileged);
        $discountEntity->save();

        $discountCollectorEntity = new SpyDiscountCollector();
        $discountCollectorEntity->setCollectorPlugin($collectorPlugin);
        $discountCollectorEntity->setFkDiscount($discountEntity->getIdDiscount());
        $discountCollectorEntity->save();

        $discountTransfer = new DiscountTransfer();
        $discountTransfer->fromArray($discountEntity->toArray(), true);

        $discountCollectorTransfer = new DiscountCollectorTransfer();
        $discountCollectorTransfer->fromArray($discountCollectorEntity->toArray(), true);
        $discountTransfer->addDiscountCollectors($discountCollectorTransfer);

        return $discountTransfer;
    }

    /**
     * @return CalculableContainer
     */
    protected function getOrderWithTwoItems()
    {
        $order = new OrderTransfer();
        $item = new ItemTransfer();

        $item->setGrossPrice(self::ITEM_GROSS_PRICE_500);
        $order->addItem($item);
        $order->addItem(clone $item);

        return new CalculableContainer($order);
    }

    /**
     * @return Calculator
     */
    protected function getCalculator()
    {
        $settings = new DiscountConfig();
        $collectorResolver = new CollectorResolver($settings);

        $messengerFacade = new MessengerFacade();
        $calculator = new Calculator($collectorResolver, $messengerFacade);

        return $calculator;
    }

}