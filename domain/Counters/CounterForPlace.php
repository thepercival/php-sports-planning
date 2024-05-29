<?php

declare(strict_types=1);

namespace SportsPlanning\Counters;

use SportsHelpers\Counter;
use SportsPlanning\Place;

/**
 * @template-extends Counter<Place>
 */
readonly class CounterForPlace extends Counter
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

    public function increment(): CounterForPlace
    {
        return new CounterForPlace($this->countedObject, $this->count + 1 );
    }

    public function decrement(): CounterForPlace
    {
        return new CounterForPlace($this->countedObject, $this->count - 1 );
    }

    public function __toString(): string
    {
        return $this->getPlaceNr() . ' ' . $this->count() . 'x';
    }
}
