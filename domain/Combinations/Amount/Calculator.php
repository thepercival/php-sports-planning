<?php

namespace SportsPlanning\Combinations\Amount;

use SportsPlanning\Combinations\Amount;

class Calculator
{

    public function __construct(private int $maxCount, private Range $range)
    {
    }

    public function maxCountBeneathMinimum(): int {
        return $this->countBeneathMinimum( [ 0 => new Amount(0, $this->maxCount ) ] );
    }

    /**
     * @param array<int, Amount> $amountMap
     * @return int
     */
    public function countBeneathMinimum(array $amountMap): int
    {
        $countBeneathMinimum = 0;
        $totalCountLessThanCount = 0;
        $hasSmallerAmount = false;
        $minAmount = $this->range->getMin()->amount;
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
        $maxAmount = $this->range->getMax()->amount;
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