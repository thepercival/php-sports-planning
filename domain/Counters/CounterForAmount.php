<?php

declare(strict_types=1);

namespace SportsPlanning\Counters;

use SportsHelpers\Counter;

/**
 * @template-extends Counter<int>
 */
readonly class CounterForAmount extends Counter
{
    public function __construct(int $amount, int $count = 0)
    {
        if( $amount < 0 || $count < 0 ) {
            throw new \Exception('amount must be at least 0 and count must be at least 0');
        }
        parent::__construct($amount, $count);
    }

    public function getAmount(): int
    {
        return $this->countedObject;
    }

    public function increment(): CounterForAmount
    {
        return new CounterForAmount($this->countedObject, $this->count + 1 );
    }

    public function decrement(): CounterForAmount
    {
        return new CounterForAmount($this->countedObject, $this->count - 1 );
    }

    public function __toString(): string
    {
        return $this->countedObject . '.' . $this->count();
    }
}
