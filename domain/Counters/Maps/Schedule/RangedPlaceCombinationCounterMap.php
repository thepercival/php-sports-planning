<?php

namespace SportsPlanning\Counters\Maps\Schedule;

use SportsPlanning\Combinations\Amount\Range as AmountRange;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Counters\Reports\RangedPlaceCombinationCountersReport;

class RangedPlaceCombinationCounterMap
{
    // private int|null $shortage = null;
    // private bool|null $overAssigned = null;

//    private readonly int $nrOfPlaceCombinationsBelowMinimum;
//    private readonly int $nrOfPlaceCombinationsAboveMaximum;
    private AmountRange $allowedRange;

    public function __construct(
        private AgainstCounterMap|TogetherCounterMap|WithCounterMap $map,
        AmountRange $allowedRange) {
        $this->allowedRange = $allowedRange;
    }

    public function getAllowedRange(): AmountRange {
        return $this->allowedRange;
    }

    /**
     * @param HomeAway $homeAway
     */
    public function addHomeAway(HomeAway $homeAway): void
    {
        $this->map->addHomeAway($homeAway);
    }

    public function addPlaceCombination(PlaceCombination $placeCombination): void {
        $this->map->addPlaceCombination($placeCombination);
    }

    public function removePlaceCombination(PlaceCombination $placeCombination): void {
        $this->map->removePlaceCombination($placeCombination);
    }

    public function count(PlaceCombination $placeCombination): int
    {
        return $this->map->count($placeCombination);
    }

    public function countAmount(int $amount): int {
        $amountMap = $this->map->calculateReport()->getAmountMap();
        return array_key_exists($amount, $amountMap) ? $amountMap[$amount]->count : 0;
    }

    public function calculateReport(): RangedPlaceCombinationCountersReport
    {
        return new RangedPlaceCombinationCountersReport($this->map, $this->allowedRange);
    }

    public function withinRange(int $nrOfCombinationsToGo): bool
    {
        $report = $this->calculateReport();
        return $this->minimumCanBeReached($nrOfCombinationsToGo) && !$report->aboveMaximum($nrOfCombinationsToGo);
    }

    public function minimumCanBeReached(int $nrOfCombinationsToGo): bool
    {
        $report = $this->calculateReport();
        return $report->minimumCanBeReached($nrOfCombinationsToGo);
    }


}