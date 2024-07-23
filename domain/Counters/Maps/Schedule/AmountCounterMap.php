<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Maps\Schedule;

use SportsPlanning\Counters\CounterForPlace;
use SportsPlanning\Counters\Maps\PlaceCounterMap;

final class AmountCounterMap extends PlaceCounterMap
{
    /**
     * @param array<int, CounterForPlace> $placeCounterMap
     */
    public function __construct(array $placeCounterMap)
    {
        parent::__construct($placeCounterMap);
    }
}
