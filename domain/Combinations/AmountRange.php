<?php

namespace SportsPlanning\Combinations;

use SportsPlanning\Combinations\Amount;

readonly class AmountRange implements \Stringable
{
    public function __construct(public Amount $min, public Amount $max) {
        if( $min->amount > $max->amount ) {
            throw new \Exception('max-amount should be at least min-amount');
        }
    }

    public function getAmountDifference(): int
    {
        return $this->max->amount - $this->min->amount;
    }

    public function __toString(): string
    {
        return '[' . $this->min . ' -> ' . $this->max . ']';
    }
}