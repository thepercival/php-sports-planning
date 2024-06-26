<?php

declare(strict_types=1);

namespace SportsPlanning\Counters;

use Countable;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\Mapper;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Counters\Maps\PlaceCombinationCounterMap;
use SportsPlanning\Counters\Maps\PlaceCounterMap;
use SportsPlanning\Poule;

class AssignedCounter
{
    protected PlaceCounterMap $assignedMap;
    protected PlaceCombinationCounterMap $assignedAgainstMap;
    protected PlaceCombinationCounterMap $assignedWithMap;
    protected PlaceCombinationCounterMap $assignedHomeMap;
    /**
     * @var array<string,array<string,CounterForPlace>>
     */
    // protected array $assignedTogetherMap = [];
    // protected bool $hasAgainstSportWithMultipleSidePlaces;

    /**
     * @param Poule $poule
     * @param list<Single|AllInOneGame|AgainstGpp|AgainstH2h> $sportVariants
     */
    public function __construct(Poule $poule, array $sportVariants)
    {
        $combinationMapper = new Mapper();
        $this->assignedMap = new PlaceCounterMap( $combinationMapper->getPlaceCounterMap($poule) );
        $this->assignedAgainstMap = new PlaceCombinationCounterMap( $combinationMapper->getAgainstMap($poule) );

        $againstVariants = array_values(array_filter($sportVariants,
            function(Single|AllInOneGame|AgainstGpp|AgainstH2h $sportVariant): bool {
                return (($sportVariant instanceof AgainstGpp) || ($sportVariant instanceof AgainstH2h));
            }));
//        $this->hasAgainstSportWithMultipleSidePlaces = count(array_filter($againstVariants,
//                function(AgainstGpp|AgainstH2h $againstVariant): bool {
//                    return $againstVariant->hasMultipleSidePlaces();
//                })) > 0;
        $withCounters = $combinationMapper->getWithMap($poule, $againstVariants);
        $this->assignedWithMap = new PlaceCombinationCounterMap( $withCounters );
        $this->assignedHomeMap = new PlaceCombinationCounterMap( $withCounters );
//        foreach ($poule->getPlaces() as $place) {
//            $this->assignedTogetherMap[(string)$place] = [];
//            foreach ($poule->getPlaces() as $coPlace) {
//                if ($coPlace === $place) {
//                    continue;
//                }
//                $this->assignedTogetherMap[(string)$place][(string)$coPlace] = new CounterForPlace($coPlace);
//            }
//        }
    }

    public function getAssignedMap(): PlaceCounterMap
    {
        return $this->assignedMap;
    }

    public function getAssignedAgainstMap(): PlaceCombinationCounterMap
    {
        return $this->assignedAgainstMap;
    }

    public function getAssignedWithMap(): PlaceCombinationCounterMap
    {
        return $this->assignedWithMap;
    }

    public function getAssignedHomeMap(): PlaceCombinationCounterMap
    {
        return $this->assignedHomeMap;
    }

    public function getAssignedAwayMap(): PlaceCombinationCounterMap
    {
        $counters = [];
        foreach( $this->assignedWithMap->getPlaceCombinationCounters() as $withCounter ) {
            $withCombination = $withCounter->getPlaceCombination();
            $nrOfAgainst = $withCounter->count() - $this->assignedHomeMap->count($withCombination);
            if( $nrOfAgainst < 0) {
                $nrOfAgainst = 0;
            }
            $counters[$withCombination->getIndex()] = new CounterForPlaceCombination($withCombination, $nrOfAgainst);
        }
        return new PlaceCombinationCounterMap($counters);
    }

    /**
     * @return array<string,array<string,CounterForPlace>>
     */
//    public function getAssignedTogetherMap(): array
//    {
//        return $this->assignedTogetherMap;
//    }

    /**
     * @param array<string,array<string,CounterForPlace>> $assignedTogetherMap
     */
//    public function setAssignedTogetherMap(array $assignedTogetherMap): void
//    {
//        $this->assignedTogetherMap = $assignedTogetherMap;
//    }

    /**
     * @param list<HomeAway> $homeAways
     */
    public function assignHomeAways(array $homeAways): void
    {
        foreach ($homeAways as $homeAway) {
            $this->assignHomeAway($homeAway);
        }
    }

    /**
     * @param HomeAway $homeAway
     */
    protected function assignHomeAway(HomeAway $homeAway): void
    {
        foreach ($homeAway->getPlaces() as $place) {
            $this->assignedMap = $this->assignedMap->addPlace($place);
        }

        foreach ($homeAway->getAgainstPlaceCombinations() as $againstPlaceCombination) {
            $this->assignedAgainstMap = $this->assignedAgainstMap->addPlaceCombination($againstPlaceCombination);
        }

        foreach ($homeAway->getWithPlaceCombinations() as $withPlaceCombination) {
            $this->assignedWithMap = $this->assignedWithMap->addPlaceCombination($withPlaceCombination);
        }

        $this->assignedHomeMap = $this->assignedHomeMap->addPlaceCombination($homeAway->getHome());

//        $this->assignToTogetherMap($homeAway->getHome());
//        $this->assignToTogetherMap($homeAway->getAway());
    }

//    public function getAssignedPlaceCounter(Place $place): PlaceCounter|null
//    {
//        $this->assignedMap->
//        if (!isset($this->assignedMap[$place->getNumber()])) {
//            return null;
//        }
//        return $this->assignedMap[$place->getNumber()];
//    }

//    public function getTogetherPlaceCounter(Place $place, Place $coPlace): CounterForPlace|null
//    {
//        if (!isset($this->assignedTogetherMap[(string)$place])
//            || !isset($this->assignedTogetherMap[(string)$place][(string)$coPlace])) {
//            return null;
//        }
//        return $this->assignedTogetherMap[(string)$place][(string)$coPlace];
//    }

    /**
     * @param PlaceCombination $placeCombinations
     * @param bool $withAssigned
     */
//    public function assignTogether(array $placeCombinations, bool $withAssigned): void
//    {
//        foreach ($placeCombinations as $placeCombination) {
//            $this->assignToTogetherMap($placeCombination);
//            if( $this->hasAgainstSportWithMultipleSidePlaces ) {
//                $this->assignedWithMap = $this->assignedWithMap->addPlaceCombination($placeCombination);
//            }
//            if( $withAssigned ) {
//                foreach( $placeCombination->getPlaces() as $place ) {
//                    $this->assignedMap = $this->assignedMap->addPlace($place);
//                }
//            }
//        }
//    }

//    /**
//     * @param PlaceCombination $placeCombination
//     */
//    protected function assignToMap(PlaceCombination $placeCombination): void
//    {
//        foreach ($placeCombination->getPlaces() as $place) {
//            $this->assignedMap[$place->getNumber()]->increment();
//        }
//    }

    /**
     * @param PlaceCombination $placeCombination
     */
//    protected function assignToTogetherMap(PlaceCombination $placeCombination): void
//    {
//        $places = $placeCombination->getPlaces();
//        foreach ($places as $placeIt) {
//            foreach ($places as $coPlace) {
//                if ($coPlace === $placeIt) {
//                    continue;
//                }
//                $this->assignedTogetherMap[(string)$placeIt][(string)$coPlace]->increment();
//            }
//        }
//    }

    public function getAmountDifference(): int {
        return $this->assignedMap->getReport()->getAmountDifference();
    }

    public function getAgainstAmountDifference(): int {
        return $this->assignedAgainstMap->getReport()->getAmountDifference();
    }

    public function getWithAmountDifference(): int {
        return $this->assignedWithMap->getReport()->getAmountDifference();
    }


    public function getHomeAmountDifference(): int {
        return $this->assignedHomeMap->getReport()->getAmountDifference();
    }

    /**
     * @param Countable $counters
     * @return int
     */
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
