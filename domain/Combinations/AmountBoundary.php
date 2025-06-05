<?php

namespace SportsPlanning\Combinations;

use SportsPlanning\Counters\CounterForAmount;

final readonly class AmountBoundary extends CounterForAmount
{
    public function __construct(int $amount, int $count)
    {
        if( $count === 0 ) {
            throw new \Exception('count must be at least 1');
        }
        parent::__construct($amount, $count);
    }
}