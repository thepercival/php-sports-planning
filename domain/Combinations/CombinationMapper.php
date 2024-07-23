<?php

namespace SportsPlanning\Combinations;

use SportsHelpers\Against\Side;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsPlanning\Counters\CounterForPlace;
use SportsPlanning\Counters\CounterForPlaceCombination;
use SportsPlanning\Counters\Maps\Schedule\SideCounterMap;
use SportsPlanning\GameRound\Together\Game;
use SportsPlanning\Place;
use SportsPlanning\Poule;

class CombinationMapper
{
    /**
     * @param Poule $poule
     * @param list<AgainstGpp|AgainstH2h> $againstVariants
     * @return array<string, CounterForPlaceCombination>
     */
    public function initWithCounterMap(Poule $poule, array $againstVariants): array
    {
        $map = [];
        foreach( $this->getNrOfSidePlaces($againstVariants) as $nrOfSidePlacesIt) {
            $this->addToPlaceCombinationMap($map, $poule, $nrOfSidePlacesIt);
        }
        return $map;
    }

    /**
     * @param Poule $poule
     * @return array<string, CounterForPlaceCombination>
     */
    public function initAgainstCounterMap(Poule $poule): array
    {
        $map = [];
        $this->addToPlaceCombinationMap($map, $poule, 2);
        return $map;
    }

    /**
     * @param list<AgainstGpp|AgainstH2h> $againstVariants
     * @return list<int>
     */
    protected function getNrOfSidePlaces(array $againstVariants): array {
        $nrOfSidePlaces = [];
        foreach( $againstVariants as $againstVariant ) {
            $nrOfSidePlaces[] = $againstVariant->getNrOfHomePlaces();
            $nrOfSidePlaces[] = $againstVariant->getNrOfAwayPlaces();
        }
        return array_values(array_unique($nrOfSidePlaces));
    }

    /**
     * @param Poule $poule
     * @param int $nrOfSidePlaces
     * @return array<string, CounterForPlaceCombination>
     */
    public function initPlaceCombinationMap(Poule $poule, int $nrOfSidePlaces): array
    {
        $map = [];
        $this->addToPlaceCombinationMap($map, $poule, $nrOfSidePlaces);
        return $map;
    }

    /**
     * @param array<string, CounterForPlaceCombination> $map
     * @param Poule $poule
     * @param int $nrOfSidePlaces
     */
    protected function addToPlaceCombinationMap(array &$map, Poule $poule, int $nrOfSidePlaces): void
    {
        foreach ($poule->getPlaces() as $place) {
            if ($nrOfSidePlaces === 1) {
                $this->addPlacesToPlaceCombinationMap($map, [$place]);
                continue;
            }
            foreach ($poule->getPlaces() as $placeIt) {
                if ($place->getPlaceNr() >= $placeIt->getPlaceNr()) {
                    continue;
                }
                if ($nrOfSidePlaces === 2) {
                    $this->addPlacesToPlaceCombinationMap($map, [$place, $placeIt]);
                }
            }
        }
    }


    /**
     * @param array<string, CounterForPlaceCombination> $map
     * @param list<Place> $places
     */
    protected function addPlacesToPlaceCombinationMap(array &$map, array $places): void
    {
        $placeCombination = new PlaceCombination($places);
        $map[$placeCombination->getIndex()] = new CounterForPlaceCombination($placeCombination);
    }


    /**
     * @param Poule $poule
     * @return array<int, CounterForPlace>
     */
    public function initPlaceCounterMap(Poule $poule): array
    {
        $map = [];
        foreach ($poule->getPlaces() as $place) {
            $map[$place->getPlaceNr()] = new CounterForPlace($place);
        }
        return $map;
    }

    /**
     * @param list<HomeAway> $homeAways
     * @return array<int, CounterForPlace>
     */
    public function initPlaceCounterMapForHomeAways(array $homeAways): array
    {
        $map = [];
        foreach ($homeAways as $homeAway) {
            foreach ($homeAway->getPlaces() as $place) {
                if( !array_key_exists($place->getPlaceNr(), $map)) {
                    $map[$place->getPlaceNr()] = new CounterForPlace($place);
                }
            }
        }
        return $map;
    }

    /**
     * @param Side $side
     * @param list<HomeAway> $homeAways
     * @return SideCounterMap
     */
    public function initAndFillSideCounterMap(Side $side, array $homeAways): SideCounterMap {
        $placeCounterMap = (new CombinationMapper())->initPlaceCounterMapForHomeAways( $homeAways );
        $sideCounterMap = new SideCounterMap($side, $placeCounterMap);
        foreach( $homeAways as $homeAway) {
            $sideCounterMap->addHomeAway($homeAway);
        }
        return $sideCounterMap;
    }
}