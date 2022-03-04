<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations\Indirect;

use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Place;

class Map
{
    /**
     * @param array<int, Counter> $counters
     */
    public function __construct(protected array $counters = [])
    {
    }

    public function addHomeAway(AgainstHomeAway $homeAway): Map
    {
        $newMap = $this;
        foreach ($homeAway->getHome()->getPlaces() as $homePlace) {
            foreach ($homeAway->getAway()->getPlaces() as $awayPlace) {
                $newMap = $newMap->add($homePlace, $awayPlace);
            }
        }
        return $newMap;
    }

    public function add(Place $start, Place $end): Map
    {
        $counters = $this->counters;
        if (!isset($counters[$start->getNumber()])) {
            $counters[$start->getNumber()] = new Counter($start);
        }
        $counters[$start->getNumber()]->add($end);

        if (!isset($counters[$end->getNumber()])) {
            $counters[$end->getNumber()] = new Counter($end);
        }
        $counters[$end->getNumber()]->add($start);
        return new Map($counters);
    }

    public function count(Place $start, Place $end, int $depth): int
    {
        $counted = [$start->getNumber() => true];
        $count = 0;
        if ($this->countHelper($start, $end, $depth, $counted, $count)) {
            return $count;
        }
        return 0;
    }

    private function countHelper(Place $current, Place $end, int $depth, array $counted, int &$count): bool
    {
        // als depth = 1 dan count teruggeven van
        $counter = $this->getCounter($current);
        if ($counter === null) {
            return false;
        }
        if ($depth === 1) {
            $count += $counter->count($end);
            return true;
        }

        $retVal = false;
        foreach ($counter->getPlaces() as $newStartPlace) {
            if (isset($counted[$newStartPlace->getNumber()])) {
                continue;
            }
            $countedIt = $counted;
            $countedIt[$newStartPlace->getNumber()] = true;
            $countIt = 0;
            if (!$this->countHelper($newStartPlace, $end, $depth - 1, $countedIt, $countIt)) {
                continue;
            }
            $count += $countIt;
            $retVal = true;
        }
        return $retVal;
    }

    private function getCounter(Place $place): Counter|null
    {
        if (!isset($this->counters[$place->getNumber()])) {
            return null;
        }
        return $this->counters[$place->getNumber()];
    }
}
