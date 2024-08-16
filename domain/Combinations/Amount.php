<?php

namespace SportsPlanning\Combinations;

readonly class Amount implements \Stringable
{
    public function __construct(public int $amount, public int $nrOfEntitiesWithSameAmount = 0) {
        if( $amount < 0 ) {
            throw new \Exception('amount should be at least 0');
        }
        if( $nrOfEntitiesWithSameAmount < 0 ) {
            throw new \Exception('nrOfEntitiesWithSameAmount should be at least 0');
        }
    }

    public function __toString(): string
    {
        return $this->amount . '.' . $this->nrOfEntitiesWithSameAmount;
    }
//    public function islessThan(self $greaterAmount): bool {
//        return $greaterAmount->amount > $this->amount
//            || ($greaterAmount->amount === $this->amount && $greaterAmount->count > $this->count);
//    }
}