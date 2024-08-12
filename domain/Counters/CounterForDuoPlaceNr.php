<?php

declare(strict_types=1);

namespace SportsPlanning\Counters;

use SportsHelpers\Counter;
use SportsPlanning\Combinations\DuoPlaceNr;

/**
 * @template-extends Counter<DuoPlaceNr>
 */
readonly class CounterForDuoPlaceNr extends Counter implements \Stringable
{
    public function __construct(DuoPlaceNr $duoPlace, int $count = 0)
    {
        parent::__construct($duoPlace, $count);
    }

    public function getDuoPlaceNr(): DuoPlaceNr
    {
        return $this->countedObject;
    }

    public function decrement(): self
    {
        return new self($this->getDuoPlaceNr(), $this->count() - 1 );
    }

    public function increment(): self
    {
        return new self($this->getDuoPlaceNr(), $this->count() + 1 );
    }

    public function getIndex(): string
    {
        return $this->getDuoPlaceNr()->getIndex();
    }

    public function __toString(): string
    {
        return $this->countedObject . ' ' . $this->count() . 'x';
    }
}
