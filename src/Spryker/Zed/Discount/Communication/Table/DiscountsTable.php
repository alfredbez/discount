<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Discount\Communication\Table;

use Orm\Zed\Discount\Persistence\Map\SpyDiscountTableMap;
use Orm\Zed\Discount\Persistence\SpyDiscount;
use Orm\Zed\Discount\Persistence\SpyDiscountQuery;
use Spryker\Service\UtilText\Model\Url\Url;
use Spryker\Zed\Gui\Communication\Table\AbstractTable;
use Spryker\Zed\Gui\Communication\Table\TableConfiguration;

class DiscountsTable extends AbstractTable
{

    const TABLE_COL_PERIOD = self::TYPE_COL_PERIOD;
    const TABLE_COL_TYPE = 'Type';
    const TYPE_COL_PERIOD = 'Period';
    const TABLE_COL_ACTIONS = 'Actions';

    const URL_PARAM_ID_DISCOUNT = 'id-discount';
    const URL_PARAM_VISIBILITY = 'visibility';
    const URL_PARAM_REDIRECT_URL = 'redirect-url';

    const DATE_FORMAT = 'Y-m-d';
    const BUTTON_ACTIVATE = 'Activate';
    const BUTTON_DEACTIVATE = 'Deactivate';

    /**
     * @var \Orm\Zed\Discount\Persistence\SpyDiscountQuery
     */
    protected $discountQuery;

    /**
     * @var array|\Spryker\Zed\Discount\Dependency\Plugin\DiscountCalculatorPluginInterface[]
     */
    protected $calculatorPlugins = [];

    /**
     * @param \Orm\Zed\Discount\Persistence\SpyDiscountQuery $discountQuery
     * @param \Spryker\Zed\Discount\Dependency\Plugin\DiscountCalculatorPluginInterface[] $calculatorPlugins
     */
    public function __construct(SpyDiscountQuery $discountQuery, array $calculatorPlugins)
    {
        $this->discountQuery = $discountQuery;
        $this->calculatorPlugins = $calculatorPlugins;
    }

    /**
     * @param \Spryker\Zed\Gui\Communication\Table\TableConfiguration $config
     *
     * @return \Spryker\Zed\Gui\Communication\Table\TableConfiguration
     */
    protected function configure(TableConfiguration $config)
    {
        $url = Url::generate('listTable')->build();
        $config->setUrl($url);

        $config->setHeader([
            SpyDiscountTableMap::COL_ID_DISCOUNT => 'Discount ID',
            SpyDiscountTableMap::COL_DISPLAY_NAME => 'Name',
            SpyDiscountTableMap::COL_AMOUNT => 'Amount',
            static::TABLE_COL_TYPE => static::TABLE_COL_TYPE,
            static::TYPE_COL_PERIOD => static::TABLE_COL_PERIOD,
            SpyDiscountTableMap::COL_IS_ACTIVE => 'Status',
            SpyDiscountTableMap::COL_IS_EXCLUSIVE => 'Exclusive',
            static::TABLE_COL_ACTIONS => static::TABLE_COL_ACTIONS,
        ]);

        $config->setSearchable([
            SpyDiscountTableMap::COL_DISPLAY_NAME,
            SpyDiscountTableMap::COL_ID_DISCOUNT,
        ]);

        $config->setSortable([
            SpyDiscountTableMap::COL_ID_DISCOUNT,
            SpyDiscountTableMap::COL_DISPLAY_NAME,
            SpyDiscountTableMap::COL_AMOUNT,
            SpyDiscountTableMap::COL_IS_ACTIVE,
            SpyDiscountTableMap::COL_IS_EXCLUSIVE,
        ]);

        $config->setDefaultSortField( SpyDiscountTableMap::COL_ID_DISCOUNT);

        $config->addRawColumn(self::TABLE_COL_ACTIONS);
        $config->addRawColumn(SpyDiscountTableMap::COL_AMOUNT);

        return $config;
    }

    /**
     * @param \Spryker\Zed\Gui\Communication\Table\TableConfiguration $config
     *
     * @return array
     */
    protected function prepareData(TableConfiguration $config)
    {
        $result = [];

        $queryResult = $this->runQuery($this->discountQuery, $config, true);

        /** @var \Orm\Zed\Discount\Persistence\SpyDiscount $discountEntity */
        foreach ($queryResult as $discountEntity) {

            $result[] = [
                SpyDiscountTableMap::COL_ID_DISCOUNT => $discountEntity->getIdDiscount(),
                SpyDiscountTableMap::COL_DISPLAY_NAME => $discountEntity->getDisplayName(),
                SpyDiscountTableMap::COL_AMOUNT => $this->getFormattedAmount($discountEntity),
                static::TABLE_COL_TYPE => $this->getDiscountType($discountEntity),
                static::TYPE_COL_PERIOD => $this->createTimePeriod($discountEntity),
                SpyDiscountTableMap::COL_IS_ACTIVE => $this->getStatus($discountEntity),
                SpyDiscountTableMap::COL_IS_EXCLUSIVE => $discountEntity->getIsExclusive(),
                static::TABLE_COL_ACTIONS => $this->getActionButtons($discountEntity),
            ];
        }

        return $result;
    }

    /**
     * @param \Orm\Zed\Discount\Persistence\SpyDiscount $discountEntity
     *
     * @return string
     */
    protected function getActionButtons(SpyDiscount $discountEntity)
    {
        $buttons = [];
        $buttons[] = $this->createEditButton($discountEntity);
        $buttons[] = $this->createViewButton($discountEntity);
        $buttons[] = $this->createAddVoucherCodeButton($discountEntity);
        $buttons[] = $this->createToggleDiscountVisibilityButton($discountEntity);

        return implode(' ', $buttons);
    }

    /**
     * @param \Orm\Zed\Discount\Persistence\SpyDiscount $discountEntity
     *
     * @return string
     */
    protected function getStatus(SpyDiscount $discountEntity)
    {
         return $discountEntity->getIsActive() ? 'Active' : 'Inactive';
    }

    /**
     * @param \Orm\Zed\Discount\Persistence\SpyDiscount $discountEntity
     *
     * @return string
     */
    protected function getDiscountType(SpyDiscount $discountEntity)
    {
        return str_replace('_', ' ', $discountEntity->getDiscountType());
    }

    /**
     * @param \Orm\Zed\Discount\Persistence\SpyDiscount $discountEntity
     *
     * @return string
     */
    protected function createEditButton(SpyDiscount $discountEntity)
    {
        $editDiscountUrl = Url::generate(
            '/discount/index/edit',
            [
                static::URL_PARAM_ID_DISCOUNT => $discountEntity->getIdDiscount(),
            ]
        );

        return $this->generateEditButton($editDiscountUrl, 'Edit');
    }

    /**
     * @param \Orm\Zed\Discount\Persistence\SpyDiscount $discountEntity
     *
     * @return string
     */
    protected function createViewButton(SpyDiscount $discountEntity)
    {
        $viewDiscountUrl = Url::generate(
            '/discount/index/view',
            [
                static::URL_PARAM_ID_DISCOUNT => $discountEntity->getIdDiscount(),
            ]
        );

        return $this->generateViewButton($viewDiscountUrl, 'View');
    }

    /**
     * @param \Orm\Zed\Discount\Persistence\SpyDiscount $discountEntity
     *
     * @return string
     */
    protected function createAddVoucherCodeButton(SpyDiscount $discountEntity)
    {
        if (!$discountEntity->getFkDiscountVoucherPool()) {
            return '';
        }

        $addVoucherCodeDiscountUrl = Url::generate(
            '/discount/index/edit',
            [
                static::URL_PARAM_ID_DISCOUNT => $discountEntity->getIdDiscount(),
            ]
        );

        return $this->generateCreateButton($addVoucherCodeDiscountUrl, 'Add code');
    }

    /**
     * @param \Orm\Zed\Discount\Persistence\SpyDiscount $discountEntity
     *
     * @return string
     */
    protected function createToggleDiscountVisibilityButton(SpyDiscount $discountEntity)
    {
        $visibility = static::BUTTON_ACTIVATE;
        if ($discountEntity->getIsActive()) {
            $visibility = static::BUTTON_DEACTIVATE;
        }

        $viewDiscountUrl = Url::generate(
            '/discount/index/toggle-discount-visibility',
            [
                static::URL_PARAM_ID_DISCOUNT => $discountEntity->getIdDiscount(),
                static::URL_PARAM_VISIBILITY => $visibility,
                static::URL_PARAM_REDIRECT_URL => '/discount/index/list',
            ]
        );

        return $this->generateStatusButton($viewDiscountUrl, $visibility);
    }

    /**
     * @param \Spryker\Service\UtilText\Model\Url\Url $viewDiscountUrl
     * @param string $visibility
     *
     * @return string
     */
    protected function generateStatusButton(Url $viewDiscountUrl, $visibility)
    {
        if ($visibility === static::BUTTON_ACTIVATE) {
            return $this->generateViewButton($viewDiscountUrl, $visibility);
        }

        return $this->generateRemoveButton($viewDiscountUrl, $visibility);
    }

    /**
     * @param \Orm\Zed\Discount\Persistence\SpyDiscount $discountEntity
     *
     * @return string
     */
    protected function createTimePeriod(SpyDiscount $discountEntity)
    {
        return $discountEntity->getValidFrom(static::DATE_FORMAT) . ' - ' . $discountEntity->getValidTo(self::DATE_FORMAT);
    }

    /**
     * @param \Orm\Zed\Discount\Persistence\SpyDiscount $discountEntity
     *
     * @return string
     */
    protected function getFormattedAmount(SpyDiscount $discountEntity)
    {
        $calculatorPlugin = $this->calculatorPlugins[$discountEntity->getCalculatorPlugin()];

        if (count($discountEntity->getDiscountAmounts()) === 0) {
            return $calculatorPlugin->getFormattedAmount($discountEntity->getAmount());
        }

        $rowTemplate = '<tr><td>GROSS</td><td>NET</td></tr>';
        $row = '';
        foreach ($discountEntity->getDiscountAmounts() as $discountAmountEntity) {

            $netAmount = '-';
            $grossAmount = '-';
            if ($discountAmountEntity->getNetAmount()) {
                $netAmount = $calculatorPlugin->getFormattedAmount(
                    $discountAmountEntity->getNetAmount(),
                    $discountAmountEntity->getCurrency()->getCode()
                );
            }

            if ($discountAmountEntity->getGrossAmount()) {
                $grossAmount = $calculatorPlugin->getFormattedAmount(
                    $discountAmountEntity->getGrossAmount(),
                    $discountAmountEntity->getCurrency()->getCode()
                );
            }

            $rowTemplate = str_replace('GROSS', $grossAmount, $rowTemplate);
            $row .= str_replace('NET', $netAmount, $rowTemplate);
        }

        $table = '
           <table width="80%">
           <tr>
                <td>Gross</td>
                <td>Net</td>
           </tr>
           ' . $row  .'
           </table>
        ';

        return $table;
    }

}
