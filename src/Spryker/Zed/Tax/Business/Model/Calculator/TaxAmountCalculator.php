<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Tax\Business\Model\Calculator;

use ArrayObject;
use Generated\Shared\Transfer\CalculableObjectTransfer;
use Generated\Shared\Transfer\ProductOptionTransfer;
use Spryker\Shared\Calculation\CalculationPriceMode;
use Spryker\Zed\Tax\Business\Model\AccruedTaxCalculatorInterface;

class TaxAmountCalculator implements CalculatorInterface
{

    /**
     * @var \Spryker\Zed\Tax\Business\Model\AccruedTaxCalculatorInterface
     */
    protected $accruedTaxCalculator;

    /**
     * @param \Spryker\Zed\Tax\Business\Model\AccruedTaxCalculatorInterface $accruedTaxCalculator
     */
    public function __construct(AccruedTaxCalculatorInterface $accruedTaxCalculator)
    {
        $this->accruedTaxCalculator = $accruedTaxCalculator;
    }

    /**
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     *
     * @return void
     */
    public function recalculate(CalculableObjectTransfer $calculableObjectTransfer)
    {
        $priceMode = $calculableObjectTransfer->getPriceMode();

        $this->calculateTaxSumAmountForItems($calculableObjectTransfer->getItems(), $priceMode);
        $this->calculateTaxSumAmountForProductOptions($calculableObjectTransfer->getItems(), $priceMode);
        $this->calculateTaxSumAmountForExpenses($calculableObjectTransfer->getExpenses(), $priceMode);

        $this->accruedTaxCalculator->resetRoundingErrorDelta();

        $this->calculateTaxUnitAmountForItems($calculableObjectTransfer->getItems(), $priceMode);
        $this->calculateTaxUnitAmountForProductOptions($calculableObjectTransfer->getItems(), $priceMode);
        $this->calculateTaxUnitAmountForExpenses($calculableObjectTransfer->getExpenses(), $priceMode);

        $this->accruedTaxCalculator->resetRoundingErrorDelta();
    }

    /**
     * @param \ArrayObject|\Generated\Shared\Transfer\ItemTransfer[] $items
     * @param string $priceMode
     *
     * @return void
     */
    protected function calculateTaxSumAmountForItems(ArrayObject $items, $priceMode)
    {
        foreach ($items as $itemTransfer) {
            $itemTransfer->setSumTaxAmount(0);

            if (!$itemTransfer->getTaxRate()) {
                continue;
            }

            $taxableAmount = $itemTransfer->getSumPrice() - $itemTransfer->getSumDiscountAmountAggregation();
            if ($taxableAmount <= 0) {
                continue;
            }

            $sumTaxAmount = $this->calculateTaxAmount(
                $taxableAmount,
                $itemTransfer->getTaxRate(),
                $priceMode,
                $itemTransfer->getSku()
            );

            $itemTransfer->setSumTaxAmount($sumTaxAmount);
        }
    }

    /**
     * @param \ArrayObject|\Generated\Shared\Transfer\ItemTransfer[] $items
     * @param string $priceMode
     *
     * @return void
     */
    protected function calculateTaxSumAmountForProductOptions(ArrayObject $items, $priceMode)
    {
        foreach ($items as $itemTransfer) {
            foreach ($itemTransfer->getProductOptions() as $productOptionTransfer) {
                $productOptionTransfer->setSumTaxAmount(0);

                if (!$productOptionTransfer->getTaxRate()) {
                    continue;
                }

                $taxableAmount = $productOptionTransfer->getSumPrice() - $productOptionTransfer->getSumDiscountAmountAggregation();
                if ($taxableAmount <= 0) {
                    continue;
                }

                $sumTaxAmount = $this->calculateTaxAmount(
                    $taxableAmount,
                    $productOptionTransfer->getTaxRate(),
                    $priceMode,
                    $itemTransfer->getSku() . ProductOptionTransfer::class
                );

                $productOptionTransfer->setSumTaxAmount($sumTaxAmount);
            }
        }
    }

    /**
     * @param \ArrayObject|\Generated\Shared\Transfer\ExpenseTransfer[] $expenses
     * @param string $priceMode
     *
     * @return void
     */
    protected function calculateTaxSumAmountForExpenses(ArrayObject $expenses, $priceMode)
    {
        foreach ($expenses as $expenseTransfer) {
            $expenseTransfer->setSumTaxAmount(0);

            if (!$expenseTransfer->getTaxRate()) {
                continue;
            }

            $taxableAmount = $expenseTransfer->getSumPrice() - $expenseTransfer->getSumDiscountAmountAggregation();
            if ($taxableAmount <= 0) {
                continue;
            }

            $sumTaxAmount = $this->calculateTaxAmount(
                $taxableAmount,
                $expenseTransfer->getTaxRate(),
                $priceMode,
                $expenseTransfer->getType()
            );

            $expenseTransfer->setSumTaxAmount($sumTaxAmount);
        }
    }

    /**
     * @param \ArrayObject|\Generated\Shared\Transfer\ItemTransfer[] $items
     * @param string $priceMode
     *
     * @return void
     */
    protected function calculateTaxUnitAmountForItems(ArrayObject $items, $priceMode)
    {
        foreach ($items as $itemTransfer) {
            $itemTransfer->setUnitTaxAmount(0);

            if (!$itemTransfer->getTaxRate()) {
                continue;
            }

            $taxableAmount = $itemTransfer->getUnitPrice() - $itemTransfer->getUnitDiscountAmountAggregation();
            if ($taxableAmount <= 0) {
                continue;
            }

            $sumTaxAmount = $this->calculateTaxAmount(
                $taxableAmount,
                $itemTransfer->getTaxRate(),
                $priceMode,
                $itemTransfer->getSku()
            );

            $itemTransfer->setUnitTaxAmount($sumTaxAmount);
        }
    }

    /**
     * @param \ArrayObject|\Generated\Shared\Transfer\ItemTransfer[] $items
     * @param string $priceMode
     *
     * @return void
     */
    protected function calculateTaxUnitAmountForProductOptions(ArrayObject $items, $priceMode)
    {
        foreach ($items as $itemTransfer) {
            foreach ($itemTransfer->getProductOptions() as $productOptionTransfer) {
                $productOptionTransfer->setUnitTaxAmount(0);

                if (!$productOptionTransfer->getTaxRate()) {
                    continue;
                }

                $taxableAmount = $productOptionTransfer->getUnitPrice() - $productOptionTransfer->getUnitDiscountAmountAggregation();
                if ($taxableAmount <= 0) {
                    continue;
                }

                $sumTaxAmount = $this->calculateTaxAmount(
                    $taxableAmount,
                    $productOptionTransfer->getTaxRate(),
                    $priceMode,
                    $itemTransfer->getSku() . ProductOptionTransfer::class
                );

                $productOptionTransfer->setUnitTaxAmount($sumTaxAmount);
            }
        }
    }

    /**
     * @param \ArrayObject|\Generated\Shared\Transfer\ExpenseTransfer[] $expenses
     * @param string $priceMode
     *
     * @return void
     */
    protected function calculateTaxUnitAmountForExpenses(ArrayObject $expenses, $priceMode)
    {
        foreach ($expenses as $expenseTransfer) {
            $expenseTransfer->setUnitTaxAmount(0);

            if (!$expenseTransfer->getTaxRate()) {
                continue;
            }

            $taxableAmount = $expenseTransfer->getUnitPrice() - $expenseTransfer->getUnitDiscountAmountAggregation();
            if ($taxableAmount <= 0) {
                continue;
            }

            $sumTaxAmount = $this->calculateTaxAmount(
                $taxableAmount,
                $expenseTransfer->getTaxRate(),
                $priceMode,
                $expenseTransfer->getType()
            );

            $expenseTransfer->setUnitTaxAmount($sumTaxAmount);
        }
    }

    /**
     * @param int $price
     * @param float $taxRate
     * @param string $priceMode
     * @param string $identifier
     *
     * @return int
     */
    protected function calculateTaxAmount(
        $price,
        $taxRate,
        $priceMode,
        $identifier
    ) {

        if ($priceMode === CalculationPriceMode::PRICE_MODE_NET) {
            return $this->accruedTaxCalculator->getTaxValueFromNetPrice($price, $taxRate, $identifier);
        }

        return $this->accruedTaxCalculator->getTaxValueFromPrice($price, $taxRate, true, $identifier);
    }

}