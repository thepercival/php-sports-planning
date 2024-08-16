<?php

declare(strict_types=1);

namespace SportsPlanning\Counters;

use SportsHelpers\Counter;

/**
 * @template-extends Counter<int>
 */
readonly class CounterForPlaceNr extends Counter
{
    public function __construct(int $placeNr, int $count = 0)
    {
        if( $placeNr < 1 || $count < 0 ) {
            throw new \Exception('placeNr must be greater than 0 and count must be at least 0');
        }
        parent::__construct($placeNr, $count);
    }

    public function getPlaceNr(): int
    {
        return $this->countedObject;
    }

    public function increment(): CounterForPlaceNr
    {
        return new CounterForPlaceNr($this->countedObject, $this->count + 1 );
    }

    public function decrement(): CounterForPlaceNr
    {
        return new CounterForPlaceNr($this->countedObject, $this->count - 1 );
    }

    public function __toString(): string
    {
        return $this->countedObject . ' ' . $this->count() . 'x';
    }
}
