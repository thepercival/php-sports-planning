<?php

namespace SportsPlanning\Combinations\Amount;

use SportsPlanning\Combinations\Amount;

class Range implements \Stringable
{
    private readonly Amount $minimum;
    private readonly Amount $maximum;

    public function __construct(Amount $minimum, Amount $maximum) {
        $this->minimum = $minimum;
        $this->maximum = $maximum;
    }

    public function getMin(): Amount
    {
        return $this->minimum;
    }

    public function getMax(): Amount
    {
        return $this->maximum;
    }

    public function getAmountDifference(): int
    {
        return $this->maximum->amount - $this->minimum->amount;
    }

    public function __toString(): string
    {
        return '[' . $this->minimum . ' -> ' . $this->maximum . ']';
    }
}