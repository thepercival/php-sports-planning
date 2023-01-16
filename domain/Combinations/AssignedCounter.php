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
    protected PlaceCombinationCounterMap $assignedAgainstMap;
    protected PlaceCombinationCounterMap $assignedWithMap;
    protected PlaceCombinationCounterMap $assignedHomeMap;
    /**
     * @var array<string,array<string,PlaceCounter>>
     */
    protected array $assignedTogetherMap = [];
    protected bool $hasAgainstSport;

    /**
     * @param Poule $poule
     * @param list<Single|AllInOneGame|AgainstGpp|AgainstH2h> $sportVariants
     */
    public function __construct(Poule $poule, array $sportVariants)
    {
        $combinationMapper = new Mapper();
        $this->assignedMap = $combinationMapper->getPlaceMap($poule);

        $this->assignedAgainstMap = new PlaceCombinationCounterMap( $combinationMapper->getAgainstMap($poule) );

        $againstVariants = array_values(array_filter($sportVariants,
            function(Single|AllInOneGame|AgainstGpp|AgainstH2h $sportVariant): bool {
                return (($sportVariant instanceof AgainstGpp) || ($sportVariant instanceof AgainstH2h));
            }));
        $this->hasAgainstSport = count($againstVariants) > 0;
        $withCounters = $combinationMapper->getWithMap($poule, $againstVariants);
        $this->assignedWithMap = new PlaceCombinationCounterMap( $withCounters );
        $this->assignedHomeMap = new PlaceCombinationCounterMap( $withCounters );
        foreach ($poule->getPlaces() as $place) {
            $this->assignedTogetherMap[$place->getLocation()] = [];
            foreach ($poule->getPlaces() as $coPlace) {
                if ($coPlace === $place) {
                    continue;
                }
                $this->assignedTogetherMap[$place->getLocation()][$coPlace->getLocation()] = new PlaceCounter($coPlace);
            }
        }
    }

    /**
     * @return array<int, PlaceCounter>
     */
    public function getAssignedMap(): array
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
            $counters[$withCombination->getIndex()] = new PlaceCombinationCounter($withCombination, $nrOfAgainst);
        }
        return new PlaceCombinationCounterMap($counters);
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
            $this->assignedAgainstMap = $this->assignedAgainstMap->addPlaceCombination($againstPlaceCombination);
        }

        foreach ($homeAway->getWithPlaceCombinations() as $withPlaceCombination) {
            $this->assignedWithMap = $this->assignedWithMap->addPlaceCombination($withPlaceCombination);
        }

        $this->assignedHomeMap = $this->assignedHomeMap->addPlaceCombination($homeAway->getHome());

        $this->assignToTogetherMap($homeAway->getHome());
        $this->assignToTogetherMap($homeAway->getAway());
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
            if( $this->hasAgainstSport ) {
                $this->assignedWithMap = $this->assignedWithMap->addPlaceCombination($placeCombination);
            }
        }
    }

    /**
     * @param PlaceCombination $placeCombination
     */
    protected function assignToMap(PlaceCombination $placeCombination): void
    {
        foreach ($placeCombination->getPlaces() as $place) {
            $this->assignedMap[$place->getNumber()]->increment();
        }
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

    public function getAssignedDifference(): int {
        return $this->getMapDifference(array_values($this->assignedMap));
    }

    public function getAgainstSportAmountDifference(): int {
        return $this->assignedAgainstMap->getAmountDifference();
    }

    public function getWithSportAmountDifference(): int {
        return $this->assignedWithMap->getAmountDifference();
    }


    public function getHomeAmountDifference(): int {
        return $this->assignedHomeMap->getAmountDifference();
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
