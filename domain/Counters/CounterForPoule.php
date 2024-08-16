<?php

declare(strict_types=1);

namespace SportsPlanning\Counters;

use SportsHelpers\Counter;
use SportsPlanning\Poule;

/**
 * @template-extends Counter<Poule>
 */
readonly class CounterForPoule extends Counter
{
    public function __construct(Poule $poule, int $count = 0)
    {
        if( $count < 0 ) {
            throw new \Exception('count must be at least 0');
        }
        parent::__construct($poule, $count);
    }

    public function getPoule(): Poule
    {
        return $this->countedObject;
    }

    public function increment(): CounterForPoule
    {
        return new CounterForPoule($this->countedObject, $this->count + 1 );
    }

    public function decrement(): CounterForPoule
    {
        return new CounterForPoule($this->countedObject, $this->count - 1 );
    }

    public function __toString(): string
    {
        return $this->getPoule()->getNumber() . ' ' . $this->count() . 'x';
    }
}
