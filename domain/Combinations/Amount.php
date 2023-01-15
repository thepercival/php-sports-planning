<?php

namespace SportsPlanning\Combinations;

class Amount implements \Stringable
{
    public readonly int $amount;
    public readonly int $count;
    public function __construct(int $amount, int $count) {
        if( $amount < 0 ) {
            throw new \Exception('amount should be at least 0');
        }
        $this->amount = $amount;
//        if( $amount > 0 && $count < 1 ) {
//            throw new \Exception('count should be at least one');
//        }
        $this->count = $count;
    }

    public function __toString(): string
    {
        return $this->amount . '.' . $this->count;
    }
//    public function islessThan(self $greaterAmount): bool {
//        return $greaterAmount->amount > $this->amount
//            || ($greaterAmount->amount === $this->amount && $greaterAmount->count > $this->count);
//    }
}