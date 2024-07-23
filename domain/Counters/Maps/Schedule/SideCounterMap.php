<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Maps\Schedule;

use SportsHelpers\Against\Side;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Counters\CounterForPlace;
use SportsPlanning\Counters\Maps\PlaceCounterMap;

final class SideCounterMap extends PlaceCounterMap
{
    /**
     * @param Side $side
     * @param array<int, CounterForPlace> $placeCounterMap
     */
    public function __construct(private readonly Side $side, array $placeCounterMap)
    {
        parent::__construct($placeCounterMap);
    }

    /**
     * @param list<HomeAway> $homeAways
     * @return void
     */
    public function addHomeAways(array $homeAways): void
    {
        foreach ($homeAways as $homeAway) {
            $this->addHomeAway($homeAway);
        }
    }

    /**
     * @param HomeAway $homeAway
     */
    public function addHomeAway(HomeAway $homeAway): void
    {
        foreach ($homeAway->getPlaces($this->side) as $place) {
            $this->addPlace($place);
        }
    }
}
