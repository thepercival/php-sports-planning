<?php

namespace SportsPlanning\Combinations\Amount;

use SportsPlanning\Combinations\Amount as AmountBase;

final class Range implements \Stringable
{
    private readonly AmountBase $minimum;
    private readonly AmountBase $maximum;

    public function __construct(AmountBase $minimum, AmountBase $maximum) {
        $this->minimum = $minimum;
        $this->maximum = $maximum;
    }

    public function getMin(): AmountBase
    {
        return $this->minimum;
    }

    public function getMax(): AmountBase
    {
        return $this->maximum;
    }

    public function getAmountDifference(): int
    {
        return $this->maximum->amount - $this->minimum->amount;
    }

    #[\Override]
    public function __toString(): string
    {
        return '[' . ((string)$this->minimum) . ' -> ' . ((string)$this->maximum) . ']';
    }
}