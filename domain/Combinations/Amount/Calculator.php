<?php

namespace SportsPlanning\Combinations\Amount;

use SportsPlanning\Combinations\Amount;

class Calculator
{

    public function __construct(private int $maxCount, private Range $range)
    {
    }

    public function maxCountBelowMinimum(): int {
        return $this->calculateTotalBelowMinimum( [ 0 => new Amount(0, $this->maxCount ) ] );
    }

    /**
     * @param array<int, Amount> $amountMap
     * @return int
     */
    public function calculateTotalBelowMinimum(array $amountMap): int
    {
        $countBelowMinimum = 0;
        $totalCountLessThanCount = 0;
        $hasSmallerAmount = false;
        $minAmount = $this->range->getMin()->amount;
        while ( $amount = array_shift($amountMap) ) {
            if( $amount->amount < $minAmount ) {
                $countBelowMinimum += (int)($amount->count * ($minAmount - $amount->amount ) );
                $hasSmallerAmount = true;
            }
            if( $amount->amount <= $minAmount ) {
                $totalCountLessThanCount += $amount->count;
            }
        }
        if( $hasSmallerAmount && $totalCountLessThanCount < $this->range->getMin()->count) {
            $countBelowMinimum += ($this->range->getMin()->count - $totalCountLessThanCount);
        }
        return $countBelowMinimum;
    }

    /**
     * @param array<int, Amount> $amountMap
     * @return int
     */
    public function calculateTotalAboveMaximum(array $amountMap): int
    {
        $countAboveMaximum = 0;
        $totalCountGreaterThanOrEqualToCount = 0;
        $hasGreaterAmount = false;
        $maxAmount = $this->range->getMax()->amount;
        while ( $amount = array_shift($amountMap) ) {
            if( $amount->amount > $maxAmount ) {
                $countAboveMaximum += (int)($amount->count * ($amount->amount - $maxAmount ) );
                $hasGreaterAmount = true;
            }
            if( $amount->amount >= $maxAmount ) {
                $totalCountGreaterThanOrEqualToCount += $amount->count;
            }
        }
        if( $hasGreaterAmount && $totalCountGreaterThanOrEqualToCount > $maxAmount) {
            $countAboveMaximum += $totalCountGreaterThanOrEqualToCount - $maxAmount;
        }
        return $countAboveMaximum;
    }
}