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

    public function incrementDuoPlaceNr(DuoPlaceNr $duoPlaceNr): void {
        $this->map->decrementDuoPlaceNr($duoPlaceNr);
    }

    public function decrementDuoPlaceNr(DuoPlaceNr $duoPlaceNr): void {
        $this->map->decrementDuoPlaceNr($duoPlaceNr);
    }

    public function count(DuoPlaceNr $placeCombination): int
    {
        return $this->map->count($placeCombination);
    }

    public function createPerAmountReport(): DuoPlaceNrCountersPerAmountReport
    {
        return new DuoPlaceNrCountersPerAmountReport($this->map);
    }

    public function withinRange(int $nrOfCombinationsToGo): bool
    {
        $perAmountReport = $this->createPerAmountReport();
        return $this->minimumCanBeReached($nrOfCombinationsToGo)
            && !$this->aboveMaximum($nrOfCombinationsToGo);
    }

    public function minimumCanBeReached(int $nrOfCombinationsToGo): bool
    {
        $perAmountReport = $this->createPerAmountReport();

        $totalBelowMinimum = $perAmountReport->calculateSmallerThan($this->allowedRange->min);
        if( $totalBelowMinimum <= $nrOfCombinationsToGo ) {
            return true;
        };

        if ( $perAmountReport->range->min->getAmount() === $this->allowedRange->min->getAmount()
            && $perAmountReport->range->min->getAmount() + $nrOfCombinationsToGo <= $perAmountReport->nrOfPlaces
        ) {
            return true;
        }
        return false;
    }
     public function aboveMaximum(int $nrOfCombinationsToGo): bool
        {
        $perAmountReport = $this->createPerAmountReport();

        $totalAboveMaximum = $perAmountReport->calculateGreaterThan($this->allowedRange->max);

        if( $totalAboveMaximum === 0 ) {
            return false;
        }

        if ( $perAmountReport->range->max->getAmount() === $this->allowedRange->max->getAmount()
            &&
            (
                $perAmountReport->range->max->count() + $nrOfCombinationsToGo <= $perAmountReport->nrOfPlaces
            )
        ) {
            return false;
        }
        return true;
    }





    function __clone()
    {
        $this->map = clone $this->map;
    }
}