<?php
declare(strict_types=1);

namespace SportsPlanning;

use SportsPlanning\Place;
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
        return $this->countedObject->getNumber();
    }
}
