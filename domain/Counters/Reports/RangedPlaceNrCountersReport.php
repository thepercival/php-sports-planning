<?php

namespace SportsPlanning\Counters\Reports;

use SportsPlanning\Combinations\Amount;
use SportsPlanning\Combinations\AmountCalculator as AmountCalculator;
use SportsPlanning\Combinations\AmountRange as AmountRange;
use SportsPlanning\Counters\Maps\PlaceNrCounterMap;

class RangedPlaceNrCountersReport
{
    // private int|null $shortage = null;
    // private bool|null $overAssigned = null;
    private readonly PlaceNrCountersReport $report;
    private readonly int $nrOfPossibleCombinations;
    private readonly int $totalBelowMinimum;
    private readonly int $totalAboveMaximum;
    private readonly AmountRange $allowedRange;

    public function __construct(PlaceNrCounterMap $map, AmountRange $allowedRange) {
        $this->report = $map->calculateReport();
        $this->allowedRange = $allowedRange;

        $calculator = new AmountCalculator($this->allowedRange);
        $this->totalBelowMinimum = $calculator->calculateCumulativeSmallerThanMinAmount( $this->report->getAmountMap() );
        $this->totalAboveMaximum = $calculator->calculateCumulativeGreaterThanMaxAmount( $this->report->getAmountMap() );

        $this->nrOfPossibleCombinations = $map->count();
    }

    public function getNOfPossibleCombinations(): int {
        return $this->nrOfPossibleCombinations;
    }

    public function getAllowedRange(): AmountRange {
        return $this->allowedRange;
    }

    public function getTotalBelowMinimum(): int
    {
        return $this->totalBelowMinimum;
    }

    public function getTotalAboveMaximum(): int
    {
        return $this->totalAboveMaximum;
    }

//    public function count(Place $place): int
//    {
//        return $this->map->count($place);
//    }

    /**
     * @return array<int, Amount>
     */
    public function getAmountMap(): array {
        return $this->report->getAmountMap();
    }

    public function getNrOfEntitiesWithAmount(int $amount): int {
        $amountMap = $this->report->getAmountMap();
        return array_key_exists($amount, $amountMap) ? $amountMap[$amount]->nrOfEntitiesWithSameAmount : 0;
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


    public function getNrOfEntitiesWithMinAmount(): int
    {
        return $this->report->getNrOfEntitiesWithMinAmount();
    }

    public function getMaxAmount(): int
    {
        return $this->report->getMaxAmount();
    }

    public function getNrOfEntitiesWithMaxAmount(): int
    {
        return $this->report->getNrOfEntitiesWithMaxAmount();
    }

    public function withinRange(int $nrOfCombinationsToGo): bool
    {
        return $this->minimumCanBeReached($nrOfCombinationsToGo) && !$this->aboveMaximum($nrOfCombinationsToGo);
    }

    public function minimumCanBeReached(int $nrOfCombinationsToGo): bool
    {
        if( $this->getTotalBelowMinimum() <= $nrOfCombinationsToGo ) {
            return true;
        };

        if ( $this->getMinAmount() === $this->allowedRange->min->amount
            && $this->getNrOfEntitiesWithMinAmount() + $nrOfCombinationsToGo <= $this->nrOfPossibleCombinations
        ) {
            return true;
        }
        return false;
    }

    public function aboveMaximum(int $nrOfCombinationsToGo): bool
    {
        if( $this->getTotalAboveMaximum() === 0 ) {
            return false;
        }

        if ( $this->getMaxAmount() === $this->allowedRange->max->amount
            &&
            (
            $this->getNrOfEntitiesWithMaxAmount() + $nrOfCombinationsToGo <= $this->nrOfPossibleCombinations
            )
        ) {
            return false;
        }
        return true;
    }

}