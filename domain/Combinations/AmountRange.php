<?php

namespace SportsPlanning\Combinations;

use SportsPlanning\Counters\CounterForAmount;

readonly class AmountRange implements \Stringable
{
    public function __construct(public CounterForAmount $min, public CounterForAmount $max) {
        if( $min->getAmount() > $max->getAmount() ) {
            throw new \Exception('max-amount should be at least min-amount');
        }
    }

    public function getAmountDifference(): int
    {
        return $this->max->getAmount() - $this->min->getAmount();
    }

    public function __toString(): string
    {
        return '[' . $this->min . ' -> ' . $this->max . ']';
    }
}