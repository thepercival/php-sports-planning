<?php

declare(strict_types=1);

namespace SportsPlanning;

use SportsHelpers\Counter;

/**
 * @template-extends Counter<Place>
 */
class PlaceCounter extends Counter
{
    public function __construct(Place $place, int $count = 0)
    {
        parent::__construct($place, $count);
    }

    public function getNumber(): int
    {
        return $this->getPlace()->getNumber();
    }

    public function getPlace(): Place
    {
        return $this->countedObject;
    }

    public function __toString(): string
    {
        return $this->getNumber() . ' ' . $this->count() . 'x';
    }
}
