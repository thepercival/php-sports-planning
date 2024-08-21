<?php

namespace SportsPlanning\Combinations;

use SportsPlanning\Counters\CounterForAmount;

readonly class AmountCalculator
{
    public function __construct() {

    }

    /**
     * @param CounterForAmount $minimumAmountCounter
     * @param list<CounterForAmount> $amountCounters
     * @return int
     */
    public function calculateSmallerThan(CounterForAmount $minimumAmountCounter, array $amountCounters): int
    {
        $countBelowMinimum = 0;
        $totalCountLessThanCount = 0;
        $hasSmallerAmount = false;
        $minAmount = $minimumAmountCounter->getAmount();
        while ( $amountCounter = array_shift($amountCounters) ) {
            if( $amountCounter->getAmount() < $minAmount ) {
                $countBelowMinimum += (int)($amountCounter->count() * ($minAmount - $amountCounter->getAmount() ) );
                $hasSmallerAmount = true;
            }
            if( $amountCounter->getAmount() <= $minAmount ) {
                $totalCountLessThanCount += $amountCounter->count();
            }
        }
        $deficitNrOfEntitiesSmallerThanMinAmount = $minimumAmountCounter->count() - $totalCountLessThanCount;
        if( $hasSmallerAmount && $deficitNrOfEntitiesSmallerThanMinAmount > 0 ) {
            $countBelowMinimum += $deficitNrOfEntitiesSmallerThanMinAmount * $minimumAmountCounter->getAmount();
        }
        return $countBelowMinimum;
    }

    /**
     * @param CounterForAmount $maxAmountCounter
     * @param list<CounterForAmount> $amountCounters
     * @return int
     */
    public function calculateGreaterThan(CounterForAmount $maxAmountCounter, array $amountCounters): int
    {
        $countAboveMaximum = 0;
        $totalCountGreaterThanOrEqualToMax = 0;
        $maxAmount = $maxAmountCounter->getAmount();
        while ( $amountCounter = array_shift($amountCounters) ) {
            if( $amountCounter->getAmount() > $maxAmount ) {
                $countAboveMaximum += (int)($amountCounter->count() * ($amountCounter->getAmount() - $maxAmount ) );
            }
            if( $amountCounter->getAmount() >= $maxAmount ) {
                $totalCountGreaterThanOrEqualToMax += $amountCounter->count();
            }
        }
        if( $totalCountGreaterThanOrEqualToMax > $maxAmountCounter->count() ) {
            $countAboveMaximum += $totalCountGreaterThanOrEqualToMax - $maxAmountCounter->count();
        }
        return $countAboveMaximum;
    }
}