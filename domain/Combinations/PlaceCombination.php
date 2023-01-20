<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations;

use SportsPlanning\Place;

class PlaceCombination implements \Stringable
{
    private string|null $index = null;
    /**
     * @var list<Place> $places
     */
    private array $places;

    /**
     * @param list<Place> $places
     */
    public function __construct(array $places)
    {
        uasort($places, function(Place $place1, Place $place2): int {
            return $place1->getNumber() - $place2->getNumber();
        });
        $this->places = array_values($places);
    }

    public function getIndex(): string
    {
        if( $this->index === null) {
            $this->index = (string)$this;
        }
        return $this->index;
    }

    public function getNumber(): int
    {
        $number = 0;
        foreach ($this->places as $place) {
            $number += (int) pow(2, $place->getNumber() - 1);
        }
        return $number;
    }

    protected function getPlaceNumber(Place $place): int
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
