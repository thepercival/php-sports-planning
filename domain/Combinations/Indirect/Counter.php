<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations\Indirect;

use SportsPlanning\Place;
use SportsPlanning\PlaceCounter;

class Counter
{
    /**
     * @var array<int, PlaceCounter>
     */
    protected array $counters;

    public function __construct(protected Place $start)
    {
        $this->counters = [];
//        foreach ($place->getPoule()->getPlaces() as $placeIt) {
//            $this->counters[$placeIt->getNumber()] = new PlaceCounter($placeIt);
//        }
//        unset($this->counters[$place->getNumber()]);
    }

    /**
     * @return array<int, PlaceCounter> $counters
     */
    public function getCopiedPlaceCounters(): array
    {
        return $this->counters;
    }

    public function add(Place $place): void
    {
        if (!isset($this->counters[$place->getNumber()])) {
            $this->counters[$place->getNumber()] = new PlaceCounter($place);
        }
        $this->counters[$place->getNumber()]->increment();
    }

    public function count(Place $place): int
    {
        $counter = $this->getCounter($place);
        return $counter?->count() ?? 0;
    }

    private function getCounter(Place $place): PlaceCounter|null
    {
        if (!isset($this->counters[$place->getNumber()])) {
            return null;
        }
        return $this->counters[$place->getNumber()];
    }

    /**
     * @return list<Place>
     */
    public function getPlaces(): array
    {
        return array_values(
            array_map(function (PlaceCounter $placeCounter): Place {
                return $placeCounter->getPlace();
            }, $this->counters)
        );
    }

//    public function balanced(): bool
//    {
//        $count = null;
//        foreach ($this->counters as $counter) {
//            if ($count === null) {
//                $count = $counter->count();
//            }
//            if ($count !== $counter->count()) {
//                return false;
//            }
//        }
//        return true;
//    }
//
//    public function count(PlaceCombination $placeCombination): int
//    {
//        if (!isset($this->counters[$placeCombination->getNumber()])) {
//            return 0;
//        }
//        return $this->counters[$placeCombination->getNumber()]->count();
//    }
//
//    public function totalCount(): int
//    {
//        $totalCount = 0;
//        foreach ($this->counters as $counter) {
//            $totalCount += $counter->count();
//        }
//        return $totalCount;
//    }
//
//    public function __toString(): string
//    {
//        $lines = '';
//        foreach ($this->counters as $counter) {
//            $lines .= $counter . PHP_EOL;
//        }
//        return $lines;
//    }
}
