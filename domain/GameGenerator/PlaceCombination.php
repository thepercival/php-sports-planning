<?php


namespace SportsPlanning\GameGenerator;

use SportsPlanning\Place;

class PlaceCombination
{
    /**
     * @var array | Place[]
     */
    private $places;

    public function __construct(array $places)
    {
        $this->places = $places;
    }

    public function getNumber(): int
    {
        $number = 0;
        foreach( $this->places as $place ) {
            $number += pow(2, $place->getNumber() - 1);
        }
        return $number;
    }

    public function getPlaceNumber(Place $place): int
    {
        return pow(2, $place->getNumber() - 1);
    }

    /**
     * @return array | Place[]
     */
    public function getPlaces(): array
    {
        return $this->places;
    }

    public function count(): int
    {
        return count($this->places);
    }

    public function has(Place $place): bool
    {
        return ($this->getNumber() & $this->getPlaceNumber($place)) > 0;
    }

    public function hasOverlap(PlaceCombination $placeCombination): bool
    {
        return ($this->getNumber() & $placeCombination->getNumber()) > 0;
    }

//    public function equals(PlaceCombination $placeCombination): bool
//    {
//        return ($combinationNumber->getAway() === $this->getHome() || $combinationNumber->getHome() === $this->getHome())
//            && ($combinationNumber->getAway() === $this->getAway() || $combinationNumber->getHome() === $this->getAway());
//    }

}