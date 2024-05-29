<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Maps;

use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Counters\CounterForPlaceCombination;
use SportsPlanning\Counters\Reports\PlaceCombinationCountersReport;

readonly final class PlaceCombinationCounterMap
{
    /**
     * @var array<string, CounterForPlaceCombination>
     */
    private array $map;
    private PlaceCombinationCountersReport $report;

    /**
     * @param array<string, CounterForPlaceCombination> $placeCombinationCounters
     */
    public function __construct(array $placeCombinationCounters)
    {
        $this->map = $placeCombinationCounters;
        $this->report = new PlaceCombinationCountersReport($placeCombinationCounters);
    }

    public function getReport(): PlaceCombinationCountersReport
    {
        return $this->report;
    }

    public function getPlaceCombination(string $index): PlaceCombination
    {
        return $this->map[$index]->getPlaceCombination();
    }

    public function count(PlaceCombination|null $placeCombination = null): int
    {
        if( $placeCombination === null ) {
            return count($this->map);
        }

        if( !array_key_exists($placeCombination->getIndex(), $this->map) ) {
            return 0;
        }
        return $this->map[$placeCombination->getIndex()]->count();
    }

    /**
     * @return list<CounterForPlaceCombination>
     */
    public function getPlaceCombinationCounters(): array
    {
        return array_values($this->map);
    }

    public function addPlaceCombination(PlaceCombination $placeCombination): self {

        $newCounter = $this->map[$placeCombination->getIndex()]->increment();
        $map = $this->map;
        $map[$placeCombination->getIndex()] = $newCounter;

        return new self($map);
    }

    public function removePlaceCombination(PlaceCombination $placeCombination): self {

        $newCounter = $this->map[$placeCombination->getIndex()]->decrement();
        $map = $this->map;
        $map[$placeCombination->getIndex()] = $newCounter;

        return new PlaceCombinationCounterMap($map);
    }

    /**
     * @return list<CounterForPlaceCombination>
     */
    public function getList(): array
    {
        return array_values($this->map);
    }

    public function output(LoggerInterface $logger, string $prefix, string $header): void {
        $logger->info($prefix . $header);
        $prefix = $prefix . '    ';
        $amountPerLine = 4; $counter = 0; $line = '';
        foreach( $this->getPlaceCombinationCounters() as $counterIt ) {
            $line .= $counterIt->getPlaceCombination() . ' ' . $counterIt->count() . 'x, ';
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
        foreach( $this->map as $index => $placeCombinationCounter) {
            $map[$index] = clone $placeCombinationCounter;
        }
        $this->map = $map;
    }
}
