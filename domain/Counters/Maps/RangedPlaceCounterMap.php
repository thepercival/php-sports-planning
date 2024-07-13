<?php

namespace SportsPlanning\Counters\Maps;

use SportsPlanning\Combinations\Amount\Calculator as AmountCalculator;
use SportsPlanning\Combinations\Amount\Range as AmountRange;
use SportsPlanning\Counters\Maps\PlaceCounterMap as PlaceCounterMapBase;
use SportsPlanning\Counters\Reports\RangedPlaceCountersReport;
use SportsPlanning\Place;

 class RangedPlaceCounterMap
{
    private readonly RangedPlaceCountersReport $report;
    private PlaceCounterMapBase $map;

    private readonly AmountRange $allowedRange;

    public function __construct(PlaceCounterMapBase $map, AmountRange $allowedRange) {
        $this->map = $map;
        $this->allowedRange = $allowedRange;
        $this->report = new RangedPlaceCountersReport($map, $allowedRange);
    }

    public function getAllowedRange(): AmountRange {
        return $this->allowedRange;
    }

    public function getMap(): PlaceCounterMapBase {
        return $this->map;
    }

    public function addPlace(Place $place): void {

        $this->map->addPlace($place);
    }

    public function removePlace(Place $place): void {

        $this->map->removePlace($place);
    }

    public function getNrOfPlacesBelowMinimum(): int
    {
        return $this->report->getNrOfPlacesBelowMinimum();
    }

    public function getNrOfPlacesAboveMaximum(): int
    {
        return $this->report->getNrOfPlacesAboveMaximum();
    }



//    public function count(Place $place): int
//    {
//        return $this->map->count($place);
//    }

    public function countAmount(int $amount): int {
        $amountMap = $this->map->calculateReport()->getAmountMap();
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
        if( $this->getNrOfPlacesBelowMinimum() <= $nrOfCombinationsToGo ) {
            return true;
        };

        $allowedMin = $this->allowedRange->getMin();
        $nrOfPossibleCombinations = $this->getMap()->count();

        if ( $this->getMinAmount() === $allowedMin->amount
            && $this->getCountOfMinAmount() + $nrOfCombinationsToGo <= $nrOfPossibleCombinations
        ) {
            return true;
        }
        return false;
    }

    public function aboveMaximum(int $nrOfCombinationsToGo): bool
    {
        if( $this->getNrOfPlacesAboveMaximum() === 0 ) {
            return false;
        }

        $allowedMax = $this->allowedRange->getMax();
        $nrOfPossibleCombinations = $this->getMap()->count();

        if ( $this->getMaxAmount() === $allowedMax->amount
            &&
            (
            $this->getCountOfMaxAmount() + $nrOfCombinationsToGo <= $nrOfPossibleCombinations
            )
        ) {
            return false;
        }
        return true;
    }

}