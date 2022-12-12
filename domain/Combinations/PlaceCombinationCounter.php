<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations;

use SportsHelpers\Counter;

/**
 * @template-extends Counter<PlaceCombination>
 */
class PlaceCombinationCounter extends Counter implements \Stringable
{
    public function __construct(PlaceCombination $placeCombination, int $count = 0)
    {
        parent::__construct($placeCombination, $count);
    }

    public function getPlaceCombination(): PlaceCombination
    {
        return $this->countedObject;
    }

    public function getNumber(): int
    {
        return $this->getPlaceCombination()->getNumber();
    }

    public function __toString(): string
    {
        return $this->countedObject . ' ' . $this->count() . 'x';
    }
}
