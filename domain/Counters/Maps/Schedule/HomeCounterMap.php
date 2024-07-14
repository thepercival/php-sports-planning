<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Maps\Schedule;

use SportsHelpers\Against\Side;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Counters\CounterForPlace;
use SportsPlanning\Counters\Maps\PlaceCounterMap;
use SportsPlanning\Poule;

final class HomeCounterMap extends PlaceCounterMap
{
    public function __construct(Poule $poule)
    {
        $placeCounters = [];
        foreach ($poule->getPlaces() as $place) {
            $placeCounters[] = new CounterForPlace($place);
        }
        parent::__construct($placeCounters);
    }

    /**
     * @param HomeAway $homeAway
     */
    public function addHomeAway(HomeAway $homeAway): void
    {
        foreach ($homeAway->getPlaces(Side::Home) as $place) {
            $this->addPlace($place);
        }
    }
}
