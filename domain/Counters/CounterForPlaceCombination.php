<?php

declare(strict_types=1);

namespace SportsPlanning\Counters;

use SportsHelpers\Counter;
use SportsPlanning\Combinations\PlaceCombination;

/**
 * @template-extends Counter<PlaceCombination>
 */
readonly class CounterForPlaceCombination extends Counter implements \Stringable
{
    public function __construct(PlaceCombination $placeCombination, int $count = 0)
    {
        parent::__construct($placeCombination, $count);
    }

    public function getPlaceCombination(): PlaceCombination
    {
        return $this->countedObject;
    }

    public function decrement(): self
    {
        return new self($this->getPlaceCombination(), $this->count() - 1 );
    }

    public function increment(): self
    {
        return new self($this->getPlaceCombination(), $this->count() + 1 );
    }

    public function getIndex(): string
    {
        return $this->getPlaceCombination()->getIndex();
    }

    public function __toString(): string
    {
        return $this->countedObject . ' ' . $this->count() . 'x';
    }
}
