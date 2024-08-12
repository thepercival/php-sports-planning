<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Maps;

use Psr\Log\LoggerInterface;
use SportsPlanning\Counters\CounterForPlaceNr;
use SportsPlanning\Counters\Reports\PlaceNrCountersReport;
use SportsPlanning\HomeAways\HomeAwayInterface;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

class PlaceNrCounterMap
{
    /**
     * @var array<int, CounterForPlaceNr>
     */
    private array $map;

    /**
     * @param array<int, CounterForPlaceNr> $placeCounters
     */
    public function __construct(array $placeCounters)
    {
        $this->map = $placeCounters;
    }

//    public function getPlace(int $number): Place
//    {
//        return $this->map[$number]->getPlace();
//    }

    public function calculateReport(): PlaceNrCountersReport
    {
        return new PlaceNrCountersReport($this->map);
    }

    public function count(int|null $placeNr = null): int
    {
        if( $placeNr === null ) {
            return count($this->map);
        }
        if( !array_key_exists($placeNr, $this->map) ) {
            return 0;
        }
        return $this->map[$placeNr]->count();
    }

    /**
     * @return list<CounterForPlaceNr>
     */
    public function copyPlaceNrCounters(): array
    {
        $counters = [];
        foreach( $this->map as $counter ) {
            $counters[] = new CounterForPlaceNr($counter->getPlaceNr(), $counter->count());
        }
        return $counters;
    }

    public function addHomeAway(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): void
    {
        foreach ($homeAway->convertToPlaceNrs() as $placeNr) {
            $this->addPlaceNr($placeNr);
        }
    }

    public function addPlaceNr(int $placeNr): void {
        if( !array_key_exists($placeNr, $this->map)) {
            $this->map[$placeNr] = new CounterForPlaceNr($placeNr);
        }
        $newCounter = $this->map[$placeNr]->increment();
        $this->map[$placeNr] = $newCounter;
    }

    public function removeHomeAway(HomeAwayInterface $homeAway): void
    {
        foreach ($homeAway->convertToPlaceNrs() as $placeNr) {
            $this->removePlaceNr($placeNr);
        }
    }

    public function removePlaceNr(int $placeNr): void {
        if( !array_key_exists($placeNr, $this->map)) {
            return;
        }
        $newCounter = $this->map[$placeNr]->decrement();
        $this->map[$placeNr] = $newCounter;
    }

    public function output(LoggerInterface $logger, string $prefix, string $header): void {
        $logger->info($prefix . $header);
        $prefix = $prefix . '    ';

        $amountPerLine = 4; $counter = 0; $line = '';
        foreach( $this->map as $counterIt ) {
            $line .= $counterIt->getPlaceNr() . ' ' . $counterIt->count() . 'x, ';
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
}
