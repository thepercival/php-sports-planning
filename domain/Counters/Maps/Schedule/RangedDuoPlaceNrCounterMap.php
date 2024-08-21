<?php

namespace SportsPlanning\Counters\Maps\Schedule;

use SportsPlanning\Combinations\AmountRange as AmountRange;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\CounterForDuoPlaceNr;
use SportsPlanning\Counters\Reports\DuoPlaceNrCountersPerAmountReport;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

class RangedDuoPlaceNrCounterMap
{
    // private int|null $shortage = null;
    // private bool|null $overAssigned = null;

//    private readonly int $nrOfPlaceCombinationsBelowMinimum;
//    private readonly int $nrOfPlaceCombinationsAboveMaximum;
    private AmountRange $allowedRange;

    public function __construct(
        private AgainstNrCounterMap|TogetherNrCounterMap|WithNrCounterMap $map,
        AmountRange                                                       $allowedRange) {
        $this->allowedRange = $allowedRange;
    }

    public function getAllowedRange(): AmountRange {
        return $this->allowedRange;
    }

    public function addHomeAway(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): void
    {
        if( $this->map instanceof WithNrCounterMap && $homeAway instanceof OneVsOneHomeAway) {
            return;
        }
        $this->map->addHomeAway($homeAway);
    }

//    public function incrementDuoPlaceNr(DuoPlaceNr $duoPlaceNr): void {
//        $this->map->incrementDuoPlaceNr($duoPlaceNr);
//    }
//
//    public function decrementDuoPlaceNr(DuoPlaceNr $duoPlaceNr): void {
//        $this->map->decrementDuoPlaceNr($duoPlaceNr);
//    }

    public function count(DuoPlaceNr $duoPlaceNr): int
    {
        return $this->map->count($duoPlaceNr);
    }

    public function createPerAmountReport(): DuoPlaceNrCountersPerAmountReport
    {
        return new DuoPlaceNrCountersPerAmountReport($this->map);
    }

    public function withinRange(int $nrOfCombinationsToGo): bool
    {
        return $this->allDuoPlaceNrCountersCanBeEqualOrGreaterThanMinimum($nrOfCombinationsToGo)
            && $this->allDuoPlaceNrCountersCanBeSmallerOrEqualToMaximum($nrOfCombinationsToGo);
    }

    public function allDuoPlaceNrCountersCanBeEqualOrGreaterThanMinimum(int $nrOfCombinationsToGo): bool
    {
        $perAmountReport = $this->createPerAmountReport();

        $totalBelowMinimum = $perAmountReport->calculateSmallerThan($this->allowedRange->min);
        if( $totalBelowMinimum <= $nrOfCombinationsToGo ) {
            return true;
        };

//        if ( $perAmountReport->range->min->getAmount() === $this->allowedRange->min->getAmount()
//            && $perAmountReport->range->min->getAmount() + $nrOfCombinationsToGo <= $perAmountReport->nrOfPlaces
//        ) {
//            return true;
//        }
        return false;
    }
     public function allDuoPlaceNrCountersCanBeSmallerOrEqualToMaximum(int $nrOfCombinationsToGo): bool
     {
        $perAmountReport = $this->createPerAmountReport();

        $totalSmallerMaximum = $perAmountReport->calculateSmallerThan($this->allowedRange->max);
        $totalGreaterMaximum = $perAmountReport->calculateGreaterThan($this->allowedRange->max);

        return $totalGreaterMaximum > 0 || ($totalSmallerMaximum + $nrOfCombinationsToGo) > 0;

//        if ( $perAmountReport->range->max->getAmount() === $this->allowedRange->max->getAmount()
//            &&
//            (
//                $perAmountReport->range->max->count() + $nrOfCombinationsToGo <= $perAmountReport->nrOfPlaces
//            )
//        ) {
//            return false;
//        }
//        return true;
    }





    function __clone()
    {
        $this->map = clone $this->map;
    }
}