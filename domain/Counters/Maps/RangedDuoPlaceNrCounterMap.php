<?php

namespace SportsPlanning\Counters\Maps;

use SportsPlanning\Combinations\AmountRange as AmountRange;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\Maps\Schedule\AgainstNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\TogetherNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\WithNrCounterMap;
use SportsPlanning\Counters\Reports\DuoPlaceNrCountersPerAmountReport;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

class RangedDuoPlaceNrCounterMap extends RangedNrCounterMapAbstract
{
    // private int|null $shortage = null;
    // private bool|null $overAssigned = null;

//    private readonly int $nrOfPlaceCombinationsBelowMinimum;
//    private readonly int $nrOfPlaceCombinationsAboveMaximum;


    public function __construct(
        private AgainstNrCounterMap|TogetherNrCounterMap|WithNrCounterMap $map, AmountRange $allowedRange )
    {
        parent::__construct($allowedRange);
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

    function __clone()
    {
        $this->map = clone $this->map;
    }
}