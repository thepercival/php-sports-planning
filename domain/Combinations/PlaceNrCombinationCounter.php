<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations;

use SportsHelpers\Counter;

/**
 * @template-extends Counter<PlaceNrCombination>
 */
final class PlaceNrCombinationCounter extends Counter implements \Stringable
{
    public function __construct(PlaceNrCombination $placeNrCombination, int $count = 0)
    {
        parent::__construct($placeNrCombination, $count);
    }

    public function getPlaceNrCombination(): PlaceNrCombination
    {
        return $this->countedObject;
    }

    public function decrement(): self
    {
        return new self($this->getPlaceNrCombination(), $this->count - 1 );
    }

    public function increment2(): self
    {
        return new self($this->getPlaceNrCombination(), $this->count() + 1 );
    }

    public function getIndex(): string
    {
        return $this->getPlaceNrCombination()->getIndex();
    }

    #[\Override]
    public function __toString(): string
    {
        return ((string)$this->countedObject) . ' ' . $this->count() . 'x';
    }
}
