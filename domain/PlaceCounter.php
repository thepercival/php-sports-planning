<?php

declare(strict_types=1);

namespace SportsPlanning;

use SportsHelpers\Counter;

/**
 * @template-extends Counter<Place>
 */
final class PlaceCounter extends Counter
{
    public function __construct(Place $place, int $count = 0)
    {
        parent::__construct($place, $count);
    }

    public function getPlaceNr(): int
    {
        return $this->getPlace()->getPlaceNr();
    }

    public function getPlace(): Place
    {
        return $this->countedObject;
    }

    public function decrement(): self
    {
        return new self($this->getPlace(), $this->count - 1 );
    }

    public function increment2(): self
    {
        return new self($this->getPlace(), $this->count() + 1 );
    }

    public function __toString(): string
    {
        return $this->getPlaceNr() . ' ' . $this->count() . 'x';
    }
}
