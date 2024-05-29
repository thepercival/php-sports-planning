<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations;

use SportsPlanning\Place;

readonly class PlaceCombination implements \Stringable
{
    /**
     * @var list<Place> $places
     */
    private array $places;
    private string $index;

    /**
     * @param list<Place> $places
     */
    public function __construct(array $places)
    {
        $uniquePlaces = array_unique($places);
        uasort($uniquePlaces, function(Place $place1, Place $place2): int {
            return $place1->getPlaceNr() - $place2->getPlaceNr();
        });
        $this->places = array_values($uniquePlaces);

        $this->index = (string)$this;
    }

    public function getIndex(): string
    {
        return $this->index;
    }

    public function getNumber(): int
    {
        $number = 0;
        foreach ($this->places as $place) {
            $number += pow(2, $place->getPlaceNr() - 1);
        }
        return $number;
    }

    protected function getPlaceNumber(Place $place): int
    {
        return pow(2, $place->getPlaceNr() - 1);
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
