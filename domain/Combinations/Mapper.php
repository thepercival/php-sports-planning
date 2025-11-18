<?php

namespace SportsPlanning\Combinations;

use SportsPlanning\PlaceCounter;
use SportsPlanning\Poule;
use SportsPlanning\Place;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;

final class Mapper
{
    /**
     * @param Poule $poule
     * @param list<AgainstH2h|AgainstGpp> $againstVariants
     * @return array<string, PlaceCombinationCounter>
     */
    public function getWithMap(Poule $poule, array $againstVariants): array
    {
        $map = [];
        foreach( $this->getNrOfSidePlaces($againstVariants) as $nrOfSidePlacesIt) {
            $this->addToPlaceCombinationMap($map, $poule, $nrOfSidePlacesIt);
        }
        return $map;
    }

    /**
     * @param Poule $poule
     * @return array<string, PlaceCombinationCounter>
     */
    public function getAgainstMap(Poule $poule): array
    {
        $map = [];
        $this->addToPlaceCombinationMap($map, $poule, 2);
        return $map;
    }


    /**
     * @param list<AgainstH2h|AgainstGpp> $againstVariants
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
     * @return array<string, PlaceCombinationCounter>
     */
    public function getPlaceCombinationMap(Poule $poule, int $nrOfSidePlaces): array
    {
        $map = [];
        $this->addToPlaceCombinationMap($map, $poule, $nrOfSidePlaces);
        return $map;
    }

    /**
     * @param array<string, PlaceCombinationCounter> $map
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
     * @param array<string, PlaceCombinationCounter> $map
     * @param list<Place> $places
     */
    protected function addPlacesToPlaceCombinationMap(array &$map, array $places): void
    {
        $placeCombination = new PlaceCombination($places);
        $map[$placeCombination->getIndex()] = new PlaceCombinationCounter($placeCombination);
    }


    /**
     * @param Poule $poule
     * @return array<int, PlaceCounter>
     */
    public function getPlaceMap(Poule $poule): array
    {
        $map = [];
        foreach ($poule->getPlaces() as $place) {
            $map[$place->getPlaceNr()] = new PlaceCounter($place);
        }
        return $map;
    }
}