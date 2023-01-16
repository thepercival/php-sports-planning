<?php

namespace SportsPlanning\Combinations\Amount;

use SportsPlanning\Combinations\Amount;

class Calculator
{

    public function __construct(private int $maxCount, private Range $range)
    {
    }

//    public function isBeneathMinimum(Amount $amount): bool {
//        $minAmount = $this->range->getMinAmount();
//        return $amount->amount < $minAmount
//            || ($amount->amount === $minAmount && $amount->count < $this->range->getMin()->count);
//    }
//
//    public function isAboveMaximum(Amount $amount): bool {
//        return $amount->amount < $this->minimum->amount
//            || ($amount->amount === $this->range->getMinAmount() && $amount->amount < $this->range->getMinAmount());
//    }


    /**
     * @param array<int, Amount> $amountMap
     * @return int
     */
    public function countBeneathMinimum(array $amountMap): int
    {
        $countBeneathMinimum = 0;
        $totalCountLessThanCount = 0;
        $hasSmallerAmount = false;
        $minAmount = $this->range->getMinAmount();
        while ( $amount = array_shift($amountMap) ) {
            if( $amount->amount < $minAmount ) {
                $countBeneathMinimum += (int)($amount->count * ($minAmount - $amount->amount ) );
                $hasSmallerAmount = true;
            }
            if( $amount->amount <= $minAmount ) {
                $totalCountLessThanCount += $amount->count;
            }
        }
        if( $hasSmallerAmount && $totalCountLessThanCount < $this->range->getMin()->count) {
            $countBeneathMinimum += ($this->range->getMin()->count - $totalCountLessThanCount);
        }
        return $countBeneathMinimum;
    }

    /**
     * @param array<int, Amount> $amountMap
     * @return int
     */
    public function countAboveMaximum(array $amountMap): int
    {
        $countAboveMaximum = 0;
        $totalCountGreaterThanOrEqualToCount = 0;
        $maxAmount = $this->range->getMaxAmount();
        while ( $amount = array_shift($amountMap) ) {
            if( $amount->amount > $maxAmount ) {
                $countAboveMaximum += (int)($this->maxCount * ($amount->amount - $maxAmount ) );
            }
            if( $amount->amount >= $maxAmount ) {
                $totalCountGreaterThanOrEqualToCount += $amount->count;
            }
        }
        if( $totalCountGreaterThanOrEqualToCount > $this->maxCount) {
            $countAboveMaximum += $totalCountGreaterThanOrEqualToCount - $this->maxCount;
        }
        return $countAboveMaximum;
    }
}