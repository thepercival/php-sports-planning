<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations;

use SportsPlanning\Place;

readonly class DuoPlaceNr implements \Stringable
{
    private string $index;

    public function __construct(private int $placeNrOne, private int $placeNrTwo)
    {
        $this->index = (string)$this;
    }

    /**
     * @return list<int>
     */
    public function getPlaceNrs(): array {
        return [$this->placeNrOne,$this->placeNrTwo];
    }

    public function getIndex(): string
    {
        return $this->index;
    }

    public function createUniqueNumber(): int
    {
        return array_sum( array_map( function(int $placeNr): int {
            return pow(2, $placeNr - 1);
        }, $this->getPlaceNrs() ));
    }

    private function createPoweredPlaceNumber(int $placeNr): int
    {
        return pow(2, $placeNr - 1);
    }

    public function has(int $placeNr): bool
    {
        return ($this->createUniqueNumber() & $this->createPoweredPlaceNumber($placeNr)) > 0;
    }

    public function hasOverlap(self $duoPlaceNr): bool
    {
        return ($this->createUniqueNumber() & $duoPlaceNr->createUniqueNumber()) > 0;
    }

    public function equals(self $duoPlace): bool
    {
        return ($this->createUniqueNumber() === $duoPlace->createUniqueNumber());
    }

    public function __toString(): string
    {
        return join(' & ', $this->getPlaceNrs() );
    }
}
