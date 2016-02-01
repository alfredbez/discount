<?php
/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace Spryker\Zed\Discount\Business\Collector;

use Generated\Shared\Transfer\DiscountCollectorTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Spryker\Zed\Calculation\Business\Model\CalculableInterface;

class Expense implements CollectorInterface
{

    /**
     * @param \Spryker\Zed\Calculation\Business\Model\CalculableInterface $container
     *
     * @return OrderTransfer[]
     */
    public function collect(CalculableInterface $container, DiscountCollectorTransfer $discountCollectorTransfer)
    {
        $discountableExpenses = [];

        foreach ($container->getCalculableObject()->getExpenses() as $expense) {
            $discountableExpenses[] = $expense;
        }

        return $discountableExpenses;
    }

}
