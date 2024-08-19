<?php

namespace SportsPlanning\Counters\Reports;

use SportsPlanning\Combinations\AmountCalculator as AmountCalculator;
use SportsPlanning\Combinations\AmountRange as AmountRange;
use SportsPlanning\Counters\CounterForAmount;
use SportsPlanning\Counters\Maps\Schedule\AgainstNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\TogetherNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\WithNrCounterMap;

readonly class RangedDuoPlaceNrCountersReport
{
    // private int|null $shortage = null;
    // private bool|null $overAssigned = null;
    public DuoPlaceNrCountersPerAmountReport $report;
    private int $totalBelowMinimum;
    private int $totalAboveMaximum;
    private int $nrOfPlaces;

    public function __construct(AgainstNrCounterMap|WithNrCounterMap|TogetherNrCounterMap $map, public AmountRange $allowedRange) {
        $this->report = new DuoPlaceNrCountersPerAmountReport($map);
        $this->totalBelowMinimum = $this->report->calculateSmallerThan($this->allowedRange->min);
        $this->totalAboveMaximum = $this->report->calculateGreaterThan($this->allowedRange->max);
        $this->nrOfPlaces = $map->count();
    }

    public function getAllowedRange(): AmountRange {
        return $this->allowedRange;
    }

//    public function getNrOfPossibleCombinations(): int {
//        return $this->nrOfPossibleCombinations;
//    }

    public function getTotalBelowMinimum(): int
    {
        return $this->totalBelowMinimum;
    }

    public function getTotalAboveMaximum(): int
    {
        return $this->totalAboveMaximum;
    }

//    /**
//     * @return array<int, CounterForAmount>
//     */
//    public function getAmountMap(): array {
//        return $this->report->getAmountMap();
//    }

//    public function getNrOfEntitiesWithAmount(int $amount): int {
//        $amountMap = $this->report->getAmountMap();
//        return array_key_exists($amount, $amountMap) ? $amountMap[$amount]->nrOfEntitiesWithSameAmount : 0;
//    }
//
//    public function getAmountDifference(): int
//    {
//        return $this->report->getAmountDifference();
//    }
//
//    public function getRange(): AmountRange|null
//    {
//        return $this->report->getRange();
//    }



    public function withinRange(int $nrOfCombinationsToGo): bool
    {
        return $this->minimumCanBeReached($nrOfCombinationsToGo) && !$this->aboveMaximum($nrOfCombinationsToGo);
    }

    public function minimumCanBeReached(int $nrOfCombinationsToGo): bool
    {
        if( $this->totalBelowMinimum <= $nrOfCombinationsToGo ) {
            return true;
        };

        if ( $this->report->range->min->getAmount() === $this->allowedRange->min->getAmount()
            && $this->report->range->min->getAmount() + $nrOfCombinationsToGo <= $this->nrOfPlaces
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

        if ( $this->report->range->max->getAmount() === $this->allowedRange->max->getAmount()
            &&
            (
                $this->report->range->max->count() + $nrOfCombinationsToGo <= $this->nrOfPlaces
            )
        ) {
            return false;
        }
        return true;
    }


}