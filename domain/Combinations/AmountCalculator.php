<?php

namespace SportsPlanning\Combinations;

readonly class AmountCalculator
{

    public function __construct(private AmountRange $range)
    {
    }

    /**
     * @param array<int, Amount> $amountMap
     * @return int
     */
    public function calculateCumulativeSmallerThanMinAmount(array $amountMap): int
    {
        $countBelowMinimum = 0;
        $totalCountLessThanCount = 0;
        $hasSmallerAmount = false;
        $minAmount = $this->range->min->amount;
        while ( $amount = array_shift($amountMap) ) {
            if( $amount->amount < $minAmount ) {
                $countBelowMinimum += (int)($amount->nrOfEntitiesWithSameAmount * ($minAmount - $amount->amount ) );
                $hasSmallerAmount = true;
            }
            if( $amount->amount <= $minAmount ) {
                $totalCountLessThanCount += $amount->nrOfEntitiesWithSameAmount;
            }
        }
        $deficitNrOfEntitiesSmallerThanMinAmount = $this->range->min->nrOfEntitiesWithSameAmount - $totalCountLessThanCount;
        if( $hasSmallerAmount && $deficitNrOfEntitiesSmallerThanMinAmount > 0 ) {
            $countBelowMinimum += $deficitNrOfEntitiesSmallerThanMinAmount * $this->range->min->amount;
        }
        return $countBelowMinimum;
    }

    /**
     * @param array<int, Amount> $amountMap
     * @return int
     */
    public function calculateCumulativeGreaterThanMaxAmount(array $amountMap): int
    {
        $countAboveMaximum = 0;
        $totalCountGreaterThanOrEqualToMax = 0;
        $maxAmount = $this->range->max->amount;
        while ( $amount = array_shift($amountMap) ) {
            if( $amount->amount > $maxAmount ) {
                $countAboveMaximum += (int)($amount->nrOfEntitiesWithSameAmount * ($amount->amount - $maxAmount ) );
            }
            if( $amount->amount >= $maxAmount ) {
                $totalCountGreaterThanOrEqualToMax += $amount->nrOfEntitiesWithSameAmount;
            }
        }
        if( $totalCountGreaterThanOrEqualToMax > $this->range->max->nrOfEntitiesWithSameAmount ) {
            $countAboveMaximum += $totalCountGreaterThanOrEqualToMax - $this->range->max->nrOfEntitiesWithSameAmount;
        }
        return $countAboveMaximum;
    }
}