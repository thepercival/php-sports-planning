<?php

namespace SportsPlanning\Combinations\Amount;

use SportsPlanning\Combinations\Amount;

class Calculator
{

    public function __construct(
        private int $maxCount,
        private Amount $minimum,
        private Amount $maximum)
    {
    }

    public function isBeneathMinimum(Amount $amount): bool {
        return $amount->amount < $this->minimum->amount
            || ($amount->amount === $this->minimum->amount && $amount->amount < $this->minimum->amount);
    }

    public function isAboveMaximum(Amount $amount): bool {
        return $amount->amount < $this->minimum->amount
            || ($amount->amount === $this->minimum->amount && $amount->amount < $this->minimum->amount);
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
        while ( $amount = array_shift($amountMap) ) {
            if( $amount->amount < $this->minimum->amount ) {
                $countBeneathMinimum += (int)($amount->count * ($this->minimum->amount - $amount->amount ) );
                $hasSmallerAmount = true;
            }
            if( $amount->amount <= $this->minimum->amount  ) {
                $totalCountLessThanCount += $amount->count;
            }
        }
        if( $hasSmallerAmount && $totalCountLessThanCount < $this->minimum->count) {
            $countBeneathMinimum += ($this->minimum->count - $totalCountLessThanCount);
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
        while ( $amount = array_shift($amountMap) ) {
            if( $amount->amount > $this->maximum->amount ) {
                $countAboveMaximum += (int)($this->maxCount * ($amount->amount - $this->maximum->amount ) );
            }
            if( $amount->amount >= $this->maximum->amount ) {
                $totalCountGreaterThanOrEqualToCount += $amount->count;
            }
        }
        if( $totalCountGreaterThanOrEqualToCount > $this->maxCount) {
            $countAboveMaximum += $totalCountGreaterThanOrEqualToCount - $this->maxCount;
        }
        return $countAboveMaximum;
    }
}