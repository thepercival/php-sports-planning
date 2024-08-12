<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Maps\Schedule;

use SportsHelpers\Against\Side;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

class AllScheduleMaps
{
    protected AmountNrCounterMap $amountCounterMap;

    protected WithNrCounterMap $withCounterMap;
    protected AgainstNrCounterMap $againstCounterMap;
    protected SideNrCounterMap $homeCounterMap;
    protected SideNrCounterMap $awayCounterMap;
    protected TogetherNrCounterMap $togetherCounterMap;

//    /**
//     * @var array<string,array<string,CounterForPlace>>
//     */
//    // protected array $assignedTogetherMap = [];
//    // protected bool $hasAgainstSportWithMultipleSidePlaces;

    public function __construct(int $nrOfPlaces)
    {
        $this->amountCounterMap = new AmountNrCounterMap($nrOfPlaces);
        $this->withCounterMap = new WithNrCounterMap($nrOfPlaces);
        $this->againstCounterMap = new AgainstNrCounterMap($nrOfPlaces);
        $this->homeCounterMap = new SideNrCounterMap(Side::Home, $nrOfPlaces);
        $this->awayCounterMap = new SideNrCounterMap(Side::Away, $nrOfPlaces);
        $this->togetherCounterMap = new TogetherNrCounterMap($nrOfPlaces);
    }

    /**
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     */
    public function addHomeAways(array $homeAways): void
    {
        foreach ($homeAways as $homeAway) {
            $this->addHomeAway($homeAway);
        }
    }

    public function addHomeAway(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): void
    {
        $this->amountCounterMap->addHomeAway($homeAway);
        if( !($homeAway instanceof OneVsOneHomeAway) ) {
            $this->withCounterMap->addHomeAway($homeAway);
        }
        $this->againstCounterMap->addHomeAway($homeAway);
        $this->homeCounterMap->addHomeAway($homeAway);
        $this->awayCounterMap->addHomeAway($homeAway);
        $this->togetherCounterMap->addHomeAway($homeAway);
    }

    public function getAmountCounterMap(): AmountNrCounterMap {
        return $this->amountCounterMap;
    }

    public function getWithCounterMap(): WithNrCounterMap {
        return $this->withCounterMap;
    }

    public function getAgainstCounterMap(): AgainstNrCounterMap {
        return $this->againstCounterMap;
    }

    public function getHomeCounterMap(): SideNrCounterMap {
        return $this->homeCounterMap;
    }

    public function setHomeCounterMap(SideNrCounterMap $homeCounterMap): void {
        $this->homeCounterMap = $homeCounterMap;
    }

    public function getAwayCounterMap(): SideNrCounterMap {
        return $this->awayCounterMap;
    }

    public function setAwayCounterMap(SideNrCounterMap $awayCounterMap): void {
        $this->awayCounterMap = $awayCounterMap;
    }

    public function getTogetherCounterMap(): TogetherNrCounterMap {
        return $this->togetherCounterMap;
    }

    public function setTogetherCounterMap(TogetherNrCounterMap $togetherCounterMap): void {
        $this->togetherCounterMap = $togetherCounterMap;
    }

    function __clone()
    {
        $this->amountCounterMap = clone $this->amountCounterMap;
        $this->withCounterMap = clone $this->withCounterMap;
        $this->againstCounterMap = clone $this->againstCounterMap;
        $this->homeCounterMap = clone $this->homeCounterMap;
        $this->awayCounterMap = clone $this->awayCounterMap;
        $this->togetherCounterMap = clone $this->togetherCounterMap;
    }

//    protected function getMapDifference(array $counters): int {
//        $counts = array_map( function(Countable $counter): int {
//            return $counter->count();
//        }, $counters );
//        if( count($counts) === 0 ) {
//            return 0;
//        }
//        return max($counts) - min($counts);
//    }
}
