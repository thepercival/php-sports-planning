<?php

namespace SportsPlanning\Combinations\Amounts;


final class AmountRange implements \Stringable
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

    #[\Override]
    public function __toString(): string
    {
        return '[' . ((string)$this->minimum) . ' -> ' . ((string)$this->maximum) . ']';
    }
}