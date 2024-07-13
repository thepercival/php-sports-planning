<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Maps;

use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Counters\CounterForPlace;
use SportsPlanning\Counters\Reports\PlaceCombinationCountersReport;
use SportsPlanning\Counters\Reports\PlaceCountersReport;
use SportsPlanning\Place;

class PlaceCounterMap
{
    /**
     * @var array<int, CounterForPlace>
     */
    private array $map;

    /**
     * @param array<int, CounterForPlace> $placeCounters
     */
    public function __construct(array $placeCounters)
    {
        $this->map = $placeCounters;
    }

    public function getPlace(int $number): Place
    {
        return $this->map[$number]->getPlace();
    }

    public function calculateReport(): PlaceCountersReport
    {
        return new PlaceCountersReport($this->map);
    }

    public function count(Place|null $place = null): int
    {
        if( $place === null ) {
            return count($this->map);
        }
        if( !array_key_exists($place->getPlaceNr(), $this->map) ) {
            return 0;
        }
        return $this->map[$place->getPlaceNr()]->count();
    }

    /**
     * @return list<CounterForPlace>
     */
    protected function getPlaceCounters(): array
    {
        return array_values($this->map);
    }

    /**
     * @param HomeAway $homeAway
     */
    public function addHomeAway(HomeAway $homeAway): void
    {
        foreach ($homeAway->getPlaces() as $place) {
            $this->addPlace($place);
        }
    }

    public function addPlace(Place $place): void {

        $newCounter = $this->map[$place->getPlaceNr()]->increment();
        $this->map[$place->getPlaceNr()] = $newCounter;
    }

    public function removePlace(Place $place): void {
        $newCounter = $this->map[$place->getPlaceNr()]->decrement();
        $this->map[$place->getPlaceNr()] = $newCounter;
    }

    public function output(LoggerInterface $logger, string $prefix, string $header): void {
        $logger->info($prefix . $header);
        $prefix = $prefix . '    ';

        $amountPerLine = 4; $counter = 0; $line = '';
        foreach( $this->getPlaceCounters() as $counterIt ) {
            $line .= $counterIt->getPlace() . ' ' . $counterIt->count() . 'x, ';
            if( ++$counter === $amountPerLine ) {
                $logger->info($prefix . $line);
                $counter = 0;
                $line = '';
            }
        }
        if( strlen($line) > 0 ) {
            $logger->info( $prefix . $line);
        }
    }

    function __clone()
    {
        $map = [];
        foreach( $this->map as $number => $placeCounter) {
            $map[$number] = clone $placeCounter;
        }
        $this->map = $map;
    }

//    /**
//     * @param array<string, PlaceCombinationCounter> $map
//     * @return array<string, PlaceCombinationCounter>
//     */
//    protected function copyPlaceCombinationCounterMap(array $map): array {
//        $newMap = [];
//        foreach( $map as $idx => $counter ) {
//            $newMap[$idx] = new PlaceCombinationCounter($counter->getPlaceCombination(), $counter->count());
//        }
//        return $newMap;
//    }

}
