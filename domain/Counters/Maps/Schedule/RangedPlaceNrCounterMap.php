<?php

namespace SportsPlanning\Counters\Maps\Schedule;

use SportsPlanning\Combinations\Amount\Range as AmountRange;
use SportsPlanning\Counters\Maps\PlaceNrCounterMap;
use SportsPlanning\Counters\Reports\RangedPlaceNrCountersReport;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

class RangedPlaceNrCounterMap
{
    public function __construct(
        protected AmountNrCounterMap|SideNrCounterMap|PlaceNrCounterMap $map, protected readonly AmountRange $allowedRange) {
    }

    public function getAllowedRange(): AmountRange {
        return $this->allowedRange;
    }

    public function addHomeAway(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): void
    {
        $this->map->addHomeAway($homeAway);
    }

    public function incrementPlaceNr(int $placeNr): void {

        $this->map->incrementPlaceNr($placeNr);
    }

    public function decrementPlaceNr(int $placeNr): void {

        $this->map->decrementPlaceNr($placeNr);
    }

    /**
     * @return list<int>
     */
    public function getPlaceNrsAboveMaximum(): array {
        $max = $this->allowedRange->getMax()->amount;
        return $this->map->getPlaceNrsAbove($max);
    }

    /**
     * @return list<int>
     */
    public function getPlaceNrsBelowMinimum(): array {
        $min = $this->allowedRange->getMin()->amount;
        return $this->map->getPlaceNrsBelow($min);
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

    public function cloneAsSideNrCounterMap(): SideNrCounterMap {
        if( $this->map instanceof SideNrCounterMap) {
            return clone $this->map;
        }
        throw new \Exception('map must be a SideNrCounterMap');
    }
}