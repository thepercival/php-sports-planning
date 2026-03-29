<?php

namespace SportsPlanning\Combinations;

use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsPlanning\Place;
use SportsPlanning\Poule;

final class Mapper
{
    /**
     * @param int $nrOfPlaces
     * @param list<AgainstH2h|AgainstGpp> $againstVariants
     * @return array<string, PlaceNrCombinationCounter>
     */
    public function getWithMap(int $nrOfPlaces, array $againstVariants): array
    {
        $map = [];
        foreach( $this->getNrOfSidePlaces($againstVariants) as $nrOfSidePlacesIt) {
            $this->addToPlaceNrCombinationMap($map, $nrOfPlaces, $nrOfSidePlacesIt);
        }
        return $map;
    }

    /**
     * @param int $nrOfPlaces
     * @return array<string, PlaceNrCombinationCounter>
     */
    public function getAgainstMap(int $nrOfPlaces): array
    {
        $map = [];
        $this->addToPlaceNrCombinationMap($map, $nrOfPlaces, 2);
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
     * @param int $nrOfPlaces
     * @param int $nrOfSidePlaces
     * @return array<string, PlaceNrCombinationCounter>
     */
    public function getPlaceNrCombinationMap(int $nrOfPlaces, int $nrOfSidePlaces): array
    {
        $map = [];
        $this->addToPlaceNrCombinationMap($map, $nrOfPlaces, $nrOfSidePlaces);
        return $map;
    }

    /**
     * @param array<string, PlaceNrCombinationCounter> $map
     * @param int $nrOfPlaces
     * @param int $nrOfSidePlaces
     */
    protected function addToPlaceNrCombinationMap(array &$map, int $nrOfPlaces, int $nrOfSidePlaces): void
    {
        for ($placeNr = 1 ; $placeNr <= $nrOfPlaces ; $placeNr++) {
            if ($nrOfSidePlaces === 1) {
                $this->addPlaceNrsToPlaceNrCombinationMap($map, [$placeNr]);
                continue;
            }
            for ($coPlaceNr = 1 ; $coPlaceNr <= $nrOfPlaces ; $coPlaceNr++) {
                if ($placeNr >= $coPlaceNr) {
                    continue;
                }
                if ($nrOfSidePlaces === 2) {
                    $this->addPlaceNrsToPlaceNrCombinationMap($map, [$placeNr, $coPlaceNr]);
                }
            }
        }
    }


    /**
     * @param array<string, PlaceNrCombinationCounter> $map
     * @param list<int> $placeNrs
     */
    protected function addPlaceNrsToPlaceNrCombinationMap(array &$map, array $placeNrs): void
    {
        $placeNrCombination = new PlaceNrCombination($placeNrs);
        $map[$placeNrCombination->getIndex()] = new PlaceNrCombinationCounter($placeNrCombination);
    }


    /**
     * @param int $nrOfPlaces
     * @return array<int, PlaceNrCounter>
     */
    public function getPlaceNrMap(int $nrOfPlaces): array
    {
        $map = [];
        for ($placeNr = 1 ; $placeNr <= $nrOfPlaces ; $placeNr++) {
            $map[$placeNr] = new PlaceNrCounter($placeNr);
        }
        return $map;
    }
}