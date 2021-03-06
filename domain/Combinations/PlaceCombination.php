<?php
declare(strict_types=1);

namespace SportsPlanning\Combinations;

use SportsPlanning\Place;

class PlaceCombination implements \Stringable
{
    /**
     * @param list<Place> $places
     */
    public function __construct(private array $places)
    {
    }

    public function getNumber(): int
    {
        $number = 0;
        foreach ($this->places as $place) {
            $number += (int) pow(2, $place->getNumber() - 1);
        }
        return $number;
    }

    public function getPlaceNumber(Place $place): int
    {
        return (int)pow(2, $place->getNumber() - 1);
    }

    /**
     * @return list<Place>
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

    public function equals(PlaceCombination $placeCombination): bool
    {
        return ($this->getNumber() === $placeCombination->getNumber());
    }

    public function __toString(): string
    {
        return join(' & ', $this->places);
    }
}
