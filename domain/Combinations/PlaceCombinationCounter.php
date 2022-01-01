<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations;

use SportsHelpers\Counter;

/**
 * @template-extends Counter<PlaceCombination>
 */
class PlaceCombinationCounter extends Counter implements \Stringable
{
    public function __construct(PlaceCombination $placeCombination)
    {
        parent::__construct($placeCombination);
    }

    public function getNumber(): int
    {
        return $this->countedObject->getNumber();
    }

    public function __toString(): string
    {
        return $this->countedObject . ' ' . $this->count() . 'x';
    }
}
