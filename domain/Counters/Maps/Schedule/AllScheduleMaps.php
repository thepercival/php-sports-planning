<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Maps\Schedule;

use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Counters\CounterForPlace;
use SportsPlanning\Counters\Maps\PlaceCounterMap;
use SportsPlanning\Poule;

class AllScheduleMaps
{
    protected AmountCounterMap $amountCounterMap;

    protected WithCounterMap $withCounterMap;
    protected AgainstCounterMap $againstCounterMap;
    protected HomeCounterMap $homeCounterMap;
    protected TogetherCounterMap $togetherCounterMap;

//    /**
//     * @var array<string,array<string,CounterForPlace>>
//     */
//    // protected array $assignedTogetherMap = [];
//    // protected bool $hasAgainstSportWithMultipleSidePlaces;

    /**
     * @param Poule $poule
     * @param list<Single|AllInOneGame|AgainstGpp|AgainstH2h> $sportVariants
     */
    public function __construct(Poule $poule, array $sportVariants)
    {
        $this->amountCounterMap = new AmountCounterMap($poule);
        $this->withCounterMap = new WithCounterMap($poule, $sportVariants);
        $this->againstCounterMap = new AgainstCounterMap($poule);
        $this->homeCounterMap = new HomeCounterMap($poule);
        $this->togetherCounterMap = new TogetherCounterMap($poule);
    }
//
//    public function getAssignedAwayMap(): PlaceCombinationCounterMap
//    {
//        $counters = [];
//        foreach( $this->assignedWithMap->getPlaceCombinationCounters() as $withCounter ) {
//            $withCombination = $withCounter->getPlaceCombination();
//            $nrOfAgainst = $withCounter->count() - $this->assignedHomeMap->count($withCombination);
//            if( $nrOfAgainst < 0) {
//                $nrOfAgainst = 0;
//            }
//            $counters[$withCombination->getIndex()] = new CounterForPlaceCombination($withCombination, $nrOfAgainst);
//        }
//        return new PlaceCombinationCounterMap($counters);
//    }
//
//    /**
//     * @return array<string,array<string,CounterForPlace>>
//     */
////    public function getAssignedTogetherMap(): array
////    {
////        return $this->assignedTogetherMap;
////    }
//
//    /**
//     * @param array<string,array<string,CounterForPlace>> $assignedTogetherMap
//     */
////    public function setAssignedTogetherMap(array $assignedTogetherMap): void
////    {
////        $this->assignedTogetherMap = $assignedTogetherMap;
////    }
//
    /**
     * @param list<HomeAway> $homeAways
     */
    public function addHomeAways(array $homeAways): void
    {
        foreach ($homeAways as $homeAway) {
            $this->addHomeAway($homeAway);
        }
    }

    /**
     * @param HomeAway $homeAway
     */
    public function addHomeAway(HomeAway $homeAway): void
    {
        $this->amountCounterMap->addHomeAway($homeAway);
        $this->withCounterMap->addHomeAway($homeAway);
        $this->againstCounterMap->addHomeAway($homeAway);
        $this->homeCounterMap->addHomeAway($homeAway);
        $this->togetherCounterMap->addHomeAway($homeAway);
    }

    public function getAmountCounterMap(): AmountCounterMap {
        return $this->amountCounterMap;
    }

    public function getWithCounterMap(): WithCounterMap {
        return $this->withCounterMap;
    }

    public function getAgainstCounterMap(): AgainstCounterMap {
        return $this->againstCounterMap;
    }

    public function getHomeCounterMap(): HomeCounterMap {
        return $this->homeCounterMap;
    }

    public function setHomeCounterMap(HomeCounterMap $homeCounterMap): void {
        $this->homeCounterMap = $homeCounterMap;
    }

    public function getTogetherCounterMap(): TogetherCounterMap {
        return $this->togetherCounterMap;
    }

    public function setTogetherCounterMap(TogetherCounterMap $togetherCounterMap): void {
        $this->togetherCounterMap = $togetherCounterMap;
    }

    public function createAwayCounterMap(): PlaceCounterMap
    {
        $counters = [];
        foreach( $this->withCounterMap->getPlaceCombinationCounters() as $withCounter ) {
            $withPlaceCombination = $withCounter->getPlaceCombination();
            foreach( $withPlaceCombination->getPlaces() as $withPlace) {
                $nrOfAgainst = $withCounter->count() - $this->homeCounterMap->count($withPlace);
                if( $nrOfAgainst < 0) {
                    $nrOfAgainst = 0;
                }
                $counters[$withPlace->getPlaceNr()] = new CounterForPlace($withPlace, $nrOfAgainst);
            }
        }
        return new PlaceCounterMap($counters);
    }

    function __clone()
    {
        $this->amountCounterMap = clone $this->amountCounterMap;
        $this->withCounterMap = clone $this->withCounterMap;
        $this->againstCounterMap = clone $this->againstCounterMap;
        $this->homeCounterMap = clone $this->homeCounterMap;
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
