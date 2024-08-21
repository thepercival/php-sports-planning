<?php

namespace SportsPlanning\Counters\Maps;

use SportsPlanning\Combinations\AmountRange as AmountRange;
use SportsPlanning\Counters\Reports\DuoPlaceNrCountersPerAmountReport;
use SportsPlanning\Counters\Reports\PlaceNrCountersPerAmountReport;

abstract class RangedNrCounterMapAbstract
{
    public function __construct(public readonly AmountRange $allowedRange) {
    }

    public function withinRange(int $countToGo): bool
    {
        return $this->allDuoPlaceNrCountersCanBeEqualOrGreaterThanMinimum($countToGo)
            && $this->allDuoPlaceNrCountersCanBeSmallerOrEqualToMaximum($countToGo);
    }

    public function allDuoPlaceNrCountersCanBeEqualOrGreaterThanMinimum(int $countToGo): bool
    {
        $perAmountReport = $this->createPerAmountReport();
        $totalBelowMinimum = $perAmountReport->calculateSmallerThan($this->allowedRange->min);
        return $countToGo >= $totalBelowMinimum;

//        if ( $perAmountReport->range->min->getAmount() === $this->allowedRange->min->getAmount()
//            && $perAmountReport->range->min->getAmount() + $nrOfCombinationsToGo <= $perAmountReport->nrOfPlaces
//        ) {
//            return true;
//        }

    }
    public function allDuoPlaceNrCountersCanBeSmallerOrEqualToMaximum(int $countToGo): bool
    {
        $perAmountReport = $this->createPerAmountReport();

        if( $perAmountReport->hasSomeGreaterThan($this->allowedRange->max) ) {
            return false;
        }
        return $countToGo <= $perAmountReport->calculateSmallerThan($this->allowedRange->max);

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
    abstract public function createPerAmountReport(): DuoPlaceNrCountersPerAmountReport | PlaceNrCountersPerAmountReport;
}