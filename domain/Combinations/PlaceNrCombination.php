<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations;

use SportsPlanning\Place;

final class PlaceNrCombination implements \Stringable
{
    private string|null $index = null;
    /**
     * @var list<int> $placeNrs
     */
    private array $placeNrs;

    /**
     * @param list<int> $placeNrs
     */
    public function __construct(array $placeNrs)
    {
        uasort($placeNrs, function(int $placeNr1, int $placeNr2): int {
            return $placeNr1 - $placeNr2;
        });
        $this->placeNrs = array_values($placeNrs);
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
        foreach ($this->placeNrs as $placeNr) {
            $number += pow(2, $placeNr - 1);
        }
        return $number;
    }

    // was getPlaceNumber
    protected function getUniquePlaceNr(int $placeNr): int
    {
        return pow(2, $placeNr - 1);
    }

    /**
     * @return list<int>
     */
    public function getPlaceNrs(): array
    {
        return $this->placeNrs;
    }

    public function count(): int
    {
        return count($this->placeNrs);
    }

    public function has(int $placeNr): bool
    {
        return ($this->getNumber() & $this->getUniquePlaceNr($placeNr)) > 0;
    }

    public function hasOverlap(PlaceNrCombination $placeNrCombination): bool
    {
        return ($this->getNumber() & $placeNrCombination->getNumber()) > 0;
    }

    public function equals(PlaceNrCombination $placeNrCombination): bool
    {
        return ($this->getNumber() === $placeNrCombination->getNumber());
    }

    #[\Override]
    public function __toString(): string
    {
        return join(' & ', $this->placeNrs);
    }
}
