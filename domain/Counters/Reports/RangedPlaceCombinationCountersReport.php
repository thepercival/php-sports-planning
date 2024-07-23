<?php

namespace SportsPlanning\Counters\Reports;

use SportsPlanning\Combinations\Amount;
use SportsPlanning\Combinations\Amount\Calculator as AmountCalculator;
use SportsPlanning\Combinations\Amount\Range as AmountRange;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Counters\Maps\PlaceCombinationCounterMap;

class RangedPlaceCombinationCountersReport
{
    // private int|null $shortage = null;
    // private bool|null $overAssigned = null;
    private readonly PlaceCombinationCountersReport $report;
    private readonly int $totalBelowMinimum;
    private readonly int $totalAboveMaximum;
    private readonly int $nrOfPossibleCombinations;
    private readonly AmountRange $allowedRange;

    public function __construct(PlaceCombinationCounterMap $map, AmountRange $allowedRange) {
        $this->report = $map->calculateReport();
        $this->allowedRange = $allowedRange;

        $calculator = new AmountCalculator($map->count(), $this->allowedRange);
        $this->totalBelowMinimum = $calculator->calculateTotalBelowMinimum( $this->report->getAmountMap() );
        $this->totalAboveMaximum = $calculator->calculateTotalAboveMaximum( $this->report->getAmountMap() );

        $this->nrOfPossibleCombinations = $map->count();
    }

    public function getAllowedRange(): AmountRange {
        return $this->allowedRange;
    }

    public function getNrOfPossibleCombinations(): int {
        return $this->nrOfPossibleCombinations;
    }

    public function getTotalBelowMinimum(): int
    {
        return $this->totalBelowMinimum;
    }

    public function getTotalAboveMaximum(): int
    {
        return $this->totalAboveMaximum;
    }

    /**
     * @return array<int, Amount>
     */
    public function getAmountMap(): array {
        return $this->report->getAmountMap();
    }

    public function countAmount(int $amount): int {
        $amountMap = $this->report->getAmountMap();
        return array_key_exists($amount, $amountMap) ? $amountMap[$amount]->count : 0;
    }

    public function getAmountDifference(): int
    {
        return $this->report->getAmountDifference();
    }

    public function getRange(): AmountRange|null
    {
        return $this->report->getRange();
    }

    public function getMinAmount(): int
    {
        return $this->report->getMinAmount();
    }


    public function getCountOfMinAmount(): int
    {
        return $this->report->getCountOfMinAmount();
    }

    public function getMaxAmount(): int
    {
        return $this->report->getMaxAmount();
    }

    public function getCountOfMaxAmount(): int
    {
        return $this->report->getCountOfMaxAmount();
    }

    public function withinRange(int $nrOfCombinationsToGo): bool
    {
        return $this->minimumCanBeReached($nrOfCombinationsToGo) && !$this->aboveMaximum($nrOfCombinationsToGo);
    }

    public function minimumCanBeReached(int $nrOfCombinationsToGo): bool
    {
        if( $this->totalBelowMinimum <= $nrOfCombinationsToGo ) {
            return true;
        };

        $allowedMin = $this->allowedRange->getMin();

        if ( $this->getMinAmount() === $allowedMin->amount
            && $this->getCountOfMinAmount() + $nrOfCombinationsToGo <= $this->nrOfPossibleCombinations
        ) {
            return true;
        }
        return false;
    }

    public function aboveMaximum(int $nrOfCombinationsToGo): bool
    {
        if( $this->totalAboveMaximum === 0 ) {
            return false;
        }

        $allowedMax = $this->allowedRange->getMax();

        if ( $this->getMaxAmount() === $allowedMax->amount
            &&
            (
            $this->getCountOfMaxAmount() + $nrOfCombinationsToGo <= $this->nrOfPossibleCombinations
            )
        ) {
            return false;
        }
        return true;
    }

}