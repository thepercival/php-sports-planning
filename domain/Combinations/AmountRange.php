<?php

namespace SportsPlanning\Combinations;

use SportsPlanning\Counters\CounterForAmount;

final readonly class AmountRange implements \Stringable
{
    public function __construct(public AmountBoundary $min, public AmountBoundary $max) {
        if( $min->getAmount() > $max->getAmount() ) {
            throw new \Exception('max-amount should be at least min-amount');
        } else if( $min->getAmount() === $max->getAmount() && $min->count() > $max->count()) {
            throw new \Exception('max-count should be at least min-count, when amount is equal');
        }
    }

    public function getAmountDifference(): int
    {
        return $this->max->getAmount() - $this->min->getAmount();
    }

    #[\Override]
    public function __toString(): string
    {
        return '[' . (string)$this->min . ' -> ' . (string)$this->max . ']';
    }
}