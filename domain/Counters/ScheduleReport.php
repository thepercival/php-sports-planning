<?php

declare(strict_types=1);

namespace SportsPlanning\Counters;

use Countable;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Counters\Maps\Schedule\AgainstCounterMap;
use SportsPlanning\Counters\Maps\Schedule\AmountCounterMap;
use SportsPlanning\Counters\Maps\Schedule\HomeCounterMap;
use SportsPlanning\Counters\Maps\Schedule\TogetherCounterMap;
use SportsPlanning\Counters\Maps\Schedule\WithCounterMap;
use SportsPlanning\Poule;

class ScheduleReport
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

    public function getTogetherCounterMap(): TogetherCounterMap {
        return $this->togetherCounterMap;
    }

//
////    public function getTogetherPlaceCounter(Place $place, Place $coPlace): CounterForPlace|null
////    {
////        if (!isset($this->assignedTogetherMap[(string)$place])
////            || !isset($this->assignedTogetherMap[(string)$place][(string)$coPlace])) {
////            return null;
////        }
////        return $this->assignedTogetherMap[(string)$place][(string)$coPlace];
////    }
////    public function assignTogether(array $placeCombinations, bool $withAssigned): void
////    {
////        foreach ($placeCombinations as $placeCombination) {
////            $this->assignToTogetherMap($placeCombination);
////            if( $this->hasAgainstSportWithMultipleSidePlaces ) {
////                $this->assignedWithMap = $this->assignedWithMap->addPlaceCombination($placeCombination);
////            }
////            if( $withAssigned ) {
////                foreach( $placeCombination->getPlaces() as $place ) {
////                    $this->assignedMap = $this->assignedMap->addPlace($place);
////                }
////            }
////        }
////    }


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
