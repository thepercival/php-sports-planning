<?php

namespace SportsPlanning\Counters\Maps\Schedule;

use SportsPlanning\Combinations\AmountRange as AmountRange;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\CounterForDuoPlaceNr;
use SportsPlanning\Counters\Reports\RangedDuoPlaceNrCountersReport;
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

    public function countAmount(int $amount): int {
        $amountMap = $this->map->calculateReport()->getAmountMap();
        return array_key_exists($amount, $amountMap) ? $amountMap[$amount]->nrOfEntitiesWithSameAmount : 0;
    }

    public function calculateReport(): RangedDuoPlaceNrCountersReport
    {
        return new RangedDuoPlaceNrCountersReport($this->map, $this->allowedRange);
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

    function __clone()
    {
        $this->map = clone $this->map;
    }
}