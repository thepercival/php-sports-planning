<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations;

use Countable;
use SportsHelpers\Counter;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;
use SportsHelpers\Sport\Variant\WithPoule\Against\EquallyAssignCalculator;
use SportsPlanning\Place;
use SportsPlanning\PlaceCounter;
use SportsPlanning\Poule;

class AssignedCounter
{
    /**
     * @var array<int, PlaceCounter>
     */
    protected array $assignedMap = [];
    /**
     * @var array<string, PlaceCombinationCounter>
     */
    protected array $assignedWithMap = [];
    /**
     * @var array<string, PlaceCombinationCounter>
     */
    protected array $assignedAgainstMap = [];
    protected PlaceCombinationCounterMap $assignedHomeMap;
    /**
     * @var array<string,array<string,PlaceCounter>>
     */
    protected array $assignedTogetherMap = [];

    protected bool $assignAgainstGppSportsEqually;

    /**
     * @param Poule $poule
     * @param list<Single|AllInOneGame|AgainstGpp|AgainstH2h> $sportVariants
     */
    public function __construct(Poule $poule, array $sportVariants)
    {
        $combinationMapper = new Mapper();
        $this->assignedMap = $combinationMapper->getPlaceMap($poule);

        $this->assignedAgainstMap = $combinationMapper->getAgainstMap($poule);
//        foreach( $sportVariants as $sportVariant ) {
//            if( )
//        }
        $againstVariants = array_values(array_filter($sportVariants,
            function(Single|AllInOneGame|AgainstGpp|AgainstH2h $sportVariant): bool {
                return (($sportVariant instanceof AgainstGpp) || ($sportVariant instanceof AgainstH2h));
            }));
        $this->assignedWithMap = $combinationMapper->getWithMap($poule, $againstVariants);
        $this->assignedHomeMap = new PlaceCombinationCounterMap( $combinationMapper->getWithMap($poule, $againstVariants) );
        foreach ($poule->getPlaces() as $place) {
            $this->assignedTogetherMap[$place->getLocation()] = [];
            foreach ($poule->getPlaces() as $coPlace) {
                if ($coPlace === $place) {
                    continue;
                }
                $this->assignedTogetherMap[$place->getLocation()][$coPlace->getLocation()] = new PlaceCounter($coPlace);
            }
        }

        $againstGppVariants = array_values(array_filter($sportVariants,
            function(Single|AllInOneGame|AgainstGpp|AgainstH2h $sportVariant): bool {
                return $sportVariant instanceof AgainstGpp;
            }));
        $calculator = new EquallyAssignCalculator();
        $this->assignAgainstGppSportsEqually = $calculator->assignAgainstSportsEqually(count($poule->getPlaceList()), $againstGppVariants);
    }

    /**
     * @return array<int, PlaceCounter>
     */
    public function getAssignedMap(): array
    {
        return $this->assignedMap;
    }

    /**
     * @return array<string, PlaceCombinationCounter>
     */
    public function getAssignedWithMap(): array
    {
        return $this->assignedWithMap;
    }

    /**
     * @return array<string, PlaceCombinationCounter>
     */
    public function getAssignedAgainstMap(): array
    {
        return $this->assignedAgainstMap;
    }

    /**
     * @return PlaceCombinationCounterMap
     */
    public function getAssignedHomeMap(): PlaceCombinationCounterMap
    {
        return $this->assignedHomeMap;
    }

    /**
     * @return array<string,array<string,PlaceCounter>>
     */
    public function getAssignedTogetherMap(): array
    {
        return $this->assignedTogetherMap;
    }

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
        $this->assignToMap($homeAway->getHome());
        $this->assignToMap($homeAway->getAway());

        foreach ($homeAway->getAgainstPlaceCombinations() as $againstPlaceCombination) {
            $this->assignToAgainstMap($againstPlaceCombination);
        }

        foreach ($homeAway->getWithPlaceCombinations() as $withPlaceCombination) {
            $this->assignToWithMap($withPlaceCombination);
        }

        $this->assignedHomeMap = $this->assignedHomeMap->addPlaceCombination($homeAway->getHome());

        $this->assignToTogetherMap($homeAway->getHome());
        $this->assignToTogetherMap($homeAway->getAway());
    }

    public function assignAgainstGppSportsEqually(): bool
    {
        return $this->assignAgainstGppSportsEqually;
    }


    public function getAssignedPlaceCounter(Place $place): PlaceCounter|null
    {
        if (!isset($this->assignedMap[$place->getNumber()])) {
            return null;
        }
        return $this->assignedMap[$place->getNumber()];
    }

    public function getTogetherPlaceCounter(Place $place, Place $coPlace): PlaceCounter|null
    {
        if (!isset($this->assignedTogetherMap[$place->getLocation()])
            || !isset($this->assignedTogetherMap[$place->getLocation()][$coPlace->getLocation()])) {
            return null;
        }
        return $this->assignedTogetherMap[$place->getLocation()][$coPlace->getLocation()];
    }

    /**
     * @param list<PlaceCombination> $placeCombinations
     */
    public function assignTogether(array $placeCombinations): void
    {
        foreach ($placeCombinations as $placeCombination) {
            $this->assignToMap($placeCombination);
            $this->assignToTogetherMap($placeCombination);
            $this->assignToWithMap($placeCombination);
        }
    }
//
//
//    /**
//     * @param PlaceCombination $placeCombination
//     */
//    protected function assignPlaceCombination(PlaceCombination $placeCombination): void
//    {

//        $this->assignToAgainstMap($placeCombination);
//    }

    /**
     * @param PlaceCombination $placeCombination
     */
    protected function assignToMap(PlaceCombination $placeCombination): void
    {
        foreach ($placeCombination->getPlaces() as $place) {
            $this->assignedMap[$place->getNumber()]->increment();
        }
    }


    protected function assignToWithMap(PlaceCombination $placeCombination): void
    {
        if (!isset($this->assignedWithMap[$placeCombination->getIndex()])) {
            $this->assignedWithMap[$placeCombination->getIndex()] = new PlaceCombinationCounter($placeCombination);
        }
        $this->assignedWithMap[$placeCombination->getIndex()]->increment();
    }

    protected function assignToAgainstMap(PlaceCombination $placeCombination): void
    {
        if (!isset($this->assignedAgainstMap[$placeCombination->getIndex()])) {
            $this->assignedAgainstMap[$placeCombination->getIndex()] = new PlaceCombinationCounter($placeCombination);
        }
        $this->assignedAgainstMap[$placeCombination->getIndex()]->increment();
    }

    /**
     * @param PlaceCombination $placeCombination
     */
    protected function assignToTogetherMap(PlaceCombination $placeCombination): void
    {
        $places = $placeCombination->getPlaces();
        foreach ($places as $placeIt) {
            foreach ($places as $coPlace) {
                if ($coPlace === $placeIt) {
                    continue;
                }
                $this->assignedTogetherMap[$placeIt->getLocation()][$coPlace->getLocation()]->increment();
            }
        }
    }

    public function getAgainstSportDifference(): int {
        return $this->getMapDifference(array_values($this->assignedAgainstMap));
    }

    public function getWithSportDifference(): int {
        return $this->getMapDifference(array_values($this->assignedWithMap));
    }

    public function getAssignedDifference(): int {
        return $this->getMapDifference(array_values($this->assignedMap));
    }

    public function getHomeAmountDifference(): int {
        return $this->assignedHomeMap->getAmountDifference();
    }

    public function getAssignedAgainstDifference(): int {
        return $this->getMapDifference(array_values($this->assignedAgainstMap));
    }

    /**
     * @param list<Countable> $counters
     * @return int
     */
    protected function getMapDifference(array $counters): int {
        $counts = array_map( function(Countable $counter): int {
            return $counter->count();
        }, $counters );
        if( count($counts) === 0 ) {
            return 0;
        }
        return max($counts) - min($counts);
    }
}
