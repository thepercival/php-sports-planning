<?php

namespace SportsPlanning\Counters\Maps\Schedule;

use SportsPlanning\Combinations\Amount\Range as AmountRange;
use SportsPlanning\Counters\CounterForPlaceNr;
use SportsPlanning\Counters\Maps\PlaceNrCounterMap;
use SportsPlanning\Counters\Reports\RangedPlaceNrCountersReport;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

class RangedPlaceNrCounterMap
{
    private readonly AmountRange $allowedRange;

    public function __construct(private AmountNrCounterMap|SideNrCounterMap|PlaceNrCounterMap $map, AmountRange $allowedRange) {
        $this->allowedRange = $allowedRange;
    }

    public function getAllowedRange(): AmountRange {
        return $this->allowedRange;
    }

    public function addHomeAway(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): void
    {
        $this->map->addHomeAway($homeAway);
    }

    public function addPlaceNr(int $placeNr): void {

        $this->map->addPlaceNr($placeNr);
    }

    public function removePlaceNr(int $placeNr): void {

        $this->map->removePlaceNr($placeNr);
    }

    /**
     * @return list<int>
     */
    public function getPlacesAboveMaximum(): array {
        $placeNrs = [];
        $max = $this->allowedRange->getMax()->amount;
        foreach( $this->map->copyPlaceNrCounters() as $placeNrCounter) {
            if( $placeNrCounter->count() > $max) {
                $placeNrs[] = $placeNrCounter->getPlaceNr();
            }
        }
        return $placeNrs;
    }

    /**
     * @return list<int>
     */
    public function getPlaceNrsBelowMinimum(): array {
        $placeNrs = [];
        $min = $this->allowedRange->getMin()->amount;
        foreach( $this->map->copyPlaceNrCounters() as $placeNrCounter) {
            if( $placeNrCounter->count() < $min) {
                $placeNrs[] = $placeNrCounter->getPlaceNr();
            }
        }
        return $placeNrs;
    }

    /**
     * @return list<CounterForPlaceNr>
     */
    public function copyPlaceNrCounters(): array
    {
        return $this->map->copyPlaceNrCounters();
    }

    public function cloneMap(): PlaceNrCounterMap
    {
        return clone $this->map;
    }

    public function calculateReport(): RangedPlaceNrCountersReport
    {
        return new RangedPlaceNrCountersReport($this->map, $this->allowedRange);
    }

    public function count(int|null $placeNr = null): int
    {
        return $this->map->count($placeNr);
    }

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
        if( $report->getTotalBelowMinimum() <= $nrOfCombinationsToGo ) {
            return true;
        };

        $allowedMin = $this->allowedRange->getMin();
        $nrOfPossibleCombinations = $report->getNOfPossibleCombinations();

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
        if( $report->getTotalAboveMaximum() === 0 ) {
            return false;
        }

        $allowedMax = $this->allowedRange->getMax();
        $nrOfPossibleCombinations = $report->getNOfPossibleCombinations();

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

    function __clone()
    {
        $this->map = clone $this->map;
    }
}