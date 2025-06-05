<?php

declare(strict_types=1);

namespace SportsPlanning\Counters;

use SportsHelpers\Counter;
use SportsPlanning\Combinations\DuoPlaceNr;

/**
 * @template-extends Counter<DuoPlaceNr>
 */
final readonly class CounterForDuoPlaceNr extends Counter implements \Stringable
{
    public function __construct(DuoPlaceNr $duoPlace, int $count = 0)
    {
        if( $count < 0 ) {
            throw new \Exception('count must be at least 0');
        }
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

    #[\Override]
    public function __toString(): string
    {
        return (string)$this->countedObject . ' ' . $this->count() . 'x';
    }
}
