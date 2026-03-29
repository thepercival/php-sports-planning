<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations;

use SportsHelpers\Counter;

/**
 * @template-extends Counter<int>
 */
final class PlaceNrCounter extends Counter
{
    public function __construct(int $placeNr, int $count = 0)
    {
        parent::__construct($placeNr, $count);
    }

    public function getPlaceNr(): int
    {
        return $this->countedObject;
    }

    public function decrement(): self
    {
        return new self($this->getPlaceNr(), $this->count - 1 );
    }

    public function increment2(): self
    {
        return new self($this->getPlaceNr(), $this->count() + 1 );
    }

    public function __toString(): string
    {
        return $this->getPlaceNr() . ' ' . $this->count() . 'x';
    }
}
