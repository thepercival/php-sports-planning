<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Maps;

use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Counters\CounterForPlaceCombination;
use SportsPlanning\Counters\Reports\PlaceCombinationCountersReport;

class PlaceCombinationCounterMap
{
    /**
     * @var array<string, CounterForPlaceCombination>
     */
    private array $map;

    /**
     * @param array<string, CounterForPlaceCombination> $placeCombinationCounters
     */
    public function __construct(array $placeCombinationCounters)
    {
        $this->map = $placeCombinationCounters;
    }

    public function calculateReport(): PlaceCombinationCountersReport
    {
        return new PlaceCombinationCountersReport($this->map);
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

//        /**
//     * @param HomeAway $homeAway
//     */
//    public function addHomeAway(HomeAway $homeAway): void
//    {
//        foreach ($homeAway->getAgainstPlaceCombinations() as $againstPlaceCombination) {
//            $this->assignedAgainstMap->addPlaceCombination($againstPlaceCombination);
//        }
//
//        foreach ($homeAway->getWithPlaceCombinations() as $withPlaceCombination) {
//            $this->assignedWithMap->addPlaceCombination($withPlaceCombination);
//        }
//
//        $this->assignedHomeMap->addPlaceCombination($homeAway->getHome());
//
////        $this->assignToTogetherMap($homeAway->getHome());
////        $this->assignToTogetherMap($homeAway->getAway());
//    }

    /**
     * @param list<PlaceCombination> $placeCombinations
     * @return void
     */
    public function addPlaceCombinations(array $placeCombinations): void {

        foreach( $placeCombinations as $placeCombination ) {
            $this->addPlaceCombination($placeCombination);
        }
    }

    public function addPlaceCombination(PlaceCombination $placeCombination): void {

        $newCounter = $this->map[$placeCombination->getIndex()]->increment();
        $this->map[$placeCombination->getIndex()] = $newCounter;
    }

    public function removePlaceCombination(PlaceCombination $placeCombination): void {

        $newCounter = $this->map[$placeCombination->getIndex()]->decrement();
        $this->map[$placeCombination->getIndex()] = $newCounter;
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
