<?php
/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Unit\Spryker\Zed\Discount\Business\QueryString\Specification;

use Spryker\Zed\Discount\Business\DiscountBusinessFactory;
use Spryker\Zed\Discount\Business\QueryString\ComparatorOperators;
use Spryker\Zed\Discount\Business\QueryString\LogicalComparators;
use Spryker\Zed\Discount\Business\QueryString\Specification\MetaDataProvider;
use Spryker\Zed\Discount\Business\QueryString\Specification\MetaProviderFactory;
use Spryker\Zed\Discount\Business\QueryString\SpecificationBuilder;

class MetaProviderFactoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @return void
     */
    public function testCreateMetaProviderByTypeForDecisionRuleShouldReturnMetaProviderForDecisionRule()
    {
        $discountBusinessFactoryMock = $this->createDiscountBusinessFactoryMock();
        $discountBusinessFactoryMock->expects($this->once())
            ->method('getDecisionRulePlugins')
            ->willReturn([]);

        $discountBusinessFactoryMock->expects($this->once())
            ->method('createComparatorOperators')
            ->willReturn($this->createComparatorOperatorsMock());

        $discountBusinessFactoryMock->expects($this->once())
            ->method('createLogicalComparators')
            ->willReturn($this->createLogicalComparatorsMock());

        $metaProviderFactoryMock = $this->createMetaProviderFactory($discountBusinessFactoryMock);

        $decisionRuleProvider = $metaProviderFactoryMock->createMetaProviderByType(
            SpecificationBuilder::TYPE_DECISION_RULE
        );

        $this->assertInstanceOf(MetaDataProvider::class, $decisionRuleProvider);

    }

    /**
     * @return void
     */
    public function testCreateMetaProviderByTypeForCollectorShouldReturnMetaProviderForCollector()
    {
        $discountBusinessFactoryMock = $this->createDiscountBusinessFactoryMock();
        $discountBusinessFactoryMock->expects($this->once())
            ->method('getCollectorPlugins')
            ->willReturn([]);

        $discountBusinessFactoryMock->expects($this->once())
            ->method('createComparatorOperators')
            ->willReturn($this->createComparatorOperatorsMock());

        $discountBusinessFactoryMock->expects($this->once())
            ->method('createLogicalComparators')
            ->willReturn($this->createLogicalComparatorsMock());

        $metaProviderFactoryMock = $this->createMetaProviderFactory($discountBusinessFactoryMock);

        $collectorProvider = $metaProviderFactoryMock->createMetaProviderByType(
            SpecificationBuilder::TYPE_COLLECTOR
        );

        $this->assertInstanceOf(MetaDataProvider::class, $collectorProvider);
    }


    /**
     * @param DiscountBusinessFactory $discountBusinessFactoryMock
     *
     * @return MetaProviderFactory
     */
    protected function createMetaProviderFactory(DiscountBusinessFactory $discountBusinessFactoryMock = null)
    {
        if (!isset($discountBusinessFactoryMock)) {
            $discountBusinessFactoryMock = $this->createDiscountBusinessFactoryMock();
        }

        return new MetaProviderFactory($discountBusinessFactoryMock);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DiscountBusinessFactory
     */
    protected function createDiscountBusinessFactoryMock()
    {
        return $this->getMock(DiscountBusinessFactory::class);
    }


    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ComparatorOperators
     */
    protected function createComparatorOperatorsMock()
    {
        return $this->getMockBuilder(ComparatorOperators::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LogicalComparators
     */
    protected function createLogicalComparatorsMock()
    {
        return $this->getMock(LogicalComparators::class);
    }
}
