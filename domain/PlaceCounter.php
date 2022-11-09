<?php

declare(strict_types=1);

namespace SportsPlanning;

use SportsHelpers\Counter;

/**
 * @template-extends Counter<Place>
 */
class PlaceCounter extends Counter
{
    public function __construct(Place $place)
    {
        parent::__construct($place);
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
