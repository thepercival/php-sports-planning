<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Maps;

use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\CounterForDuoPlaceNr;
use SportsPlanning\Counters\Reports\DuoPlaceNrCountersReport;

class DuoPlaceNrCounterMap
{
    /**
     * @var array<string, CounterForDuoPlaceNr>
     */
    private array $map;

    /**
     * @param array<string, CounterForDuoPlaceNr> $duoPlaceNrCounterMap
     */
    public function __construct(array $duoPlaceNrCounterMap)
    {
        $this->map = $duoPlaceNrCounterMap;
    }

    public function calculateReport(): DuoPlaceNrCountersReport
    {
        return new DuoPlaceNrCountersReport($this->map);
    }

    public function getDuoPlaceNr(string $index): DuoPlaceNr
    {
        return $this->map[$index]->getDuoPlaceNr();
    }

    public function count(DuoPlaceNr|null $duoPlace = null): int
    {
        if( $duoPlace === null ) {
            return count($this->map);
        }

        if( !array_key_exists($duoPlace->getIndex(), $this->map) ) {
            return 0;
        }
        return $this->map[$duoPlace->getIndex()]->count();
    }

    /**
     * @return list<CounterForDuoPlaceNr>
     */
    public function copyDuoPlaceNrCounters(): array
    {
        $counters = [];
        foreach( $this->map as $counter ) {
            $counters[] = new CounterForDuoPlaceNr($counter->getDuoPlaceNr(), $counter->count());
        }
        return $counters;
    }

    /**
     * @param list<DuoPlaceNr> $duoPlaceNrs
     * @return void
     */
    public function incrementDuoPlaceNrs(array $duoPlaceNrs): void {

        foreach( $duoPlaceNrs as $duoPlaceNr ) {
            $this->incrementDuoPlaceNr($duoPlaceNr);
        }
    }

    public function incrementDuoPlaceNr(DuoPlaceNr $duoPlaceNr): void {

        $newCounter = $this->map[$duoPlaceNr->getIndex()]->increment();
        $this->map[$duoPlaceNr->getIndex()] = $newCounter;
    }

    public function decrementDuoPlaceNr(DuoPlaceNr $duoPlaceNr): void {

        $newCounter = $this->map[$duoPlaceNr->getIndex()]->decrement();
        $this->map[$duoPlaceNr->getIndex()] = $newCounter;
    }

    public function output(LoggerInterface $logger, string $prefix, string $header): void {
        $logger->info($prefix . $header);
        $prefix = $prefix . '    ';
        $amountPerLine = 4; $counter = 0; $line = '';
        foreach( $this->map as $counterIt ) {
            $line .= $counterIt->getDuoPlaceNr() . ' ' . $counterIt->count() . 'x, ';
            if( ++$counter === $amountPerLine ) {
                $logger->info($prefix . $line);
                $counter = 0;
                $line = '';
            }
        }
        if( strlen($line) > 0 ) {
            $logger->info($prefix . $line);
        }
    }

    function __clone()
    {
        $map = [];
        foreach( $this->map as $index => $duoPlaceCounter) {
            $map[$index] = clone $duoPlaceCounter;
        }
        $this->map = $map;
    }
}
