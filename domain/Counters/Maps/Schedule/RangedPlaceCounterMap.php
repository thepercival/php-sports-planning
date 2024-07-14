<?php

namespace SportsPlanning\Counters\Maps\Schedule;

use SportsPlanning\Combinations\Amount\Range as AmountRange;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Counters\Maps\PlaceCounterMap as PlaceCounterMapBase;
use SportsPlanning\Counters\Reports\RangedPlaceCountersReport;
use SportsPlanning\Place;

class RangedPlaceCounterMap
{
    private readonly AmountRange $allowedRange;

    public function __construct(private AmountCounterMap|HomeCounterMap $map, AmountRange $allowedRange) {
        $this->map = $map;
        $this->allowedRange = $allowedRange;
    }

    public function getAllowedRange(): AmountRange {
        return $this->allowedRange;
    }

    public function getMap(): PlaceCounterMapBase {
        return $this->map;
    }

    public function addHomeAway(HomeAway $homeAway): void
    {
        $this->map->addHomeAway($homeAway);
    }

    public function addPlace(Place $place): void {

        $this->map->addPlace($place);
    }

    public function removePlace(Place $place): void {

        $this->map->removePlace($place);
    }

    public function calculateReport(): RangedPlaceCountersReport
    {
        return new RangedPlaceCountersReport($this->map, $this->allowedRange);
    }



//    public function count(Place $place): int
//    {
//        return $this->map->count($place);
//    }

    public function countAmount(int $amount): int {
        $amountMap = $this->map->calculateReport()->getAmountMap();
        return array_key_exists($amount, $amountMap) ? $amountMap[$amount]->count : 0;
    }

    public function withinRange(int $nrOfCombinationsToGo): bool
    {
        return $this->minimumCanBeReached($nrOfCombinationsToGo) && !$this->aboveMaximum($nrOfCombinationsToGo);
    }

    public function minimumCanBeReached(int $nrOfCombinationsToGo): bool
    {
        $report = $this->calculateReport();
        if( $report->getNrOfPlacesBelowMinimum() <= $nrOfCombinationsToGo ) {
            return true;
        };

        $allowedMin = $this->allowedRange->getMin();
        $nrOfPossibleCombinations = $this->getMap()->count();

        if ( $report->getMinAmount() === $allowedMin->amount
            && $report->getCountOfMinAmount() + $nrOfCombinationsToGo <= $nrOfPossibleCombinations
        ) {
            return true;
        }
        return false;
    }

    public function aboveMaximum(int $nrOfCombinationsToGo): bool
    {
        $report = $this->calculateReport();
        if( $report->getNrOfPlacesAboveMaximum() === 0 ) {
            return false;
        }

        $allowedMax = $this->allowedRange->getMax();
        $nrOfPossibleCombinations = $this->getMap()->count();

        if ( $report->getMaxAmount() === $allowedMax->amount
            &&
            (
                $report->getCountOfMaxAmount() + $nrOfCombinationsToGo <= $nrOfPossibleCombinations
            )
        ) {
            return false;
        }
        return true;
    }

}