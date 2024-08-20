<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Maps;

use Psr\Log\LoggerInterface;
use SportsPlanning\Counters\CounterForPlaceNr;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

abstract class PlaceNrCounterMapAbstract
{
    /**
     * @var non-empty-array<int, CounterForPlaceNr>
     */
    private array $map;

    public function __construct(public int $nrOfPlaces)
    {
        $placeNrCounterMapCreator = new PlaceNrCounterMapCreator();
        $this->map = $placeNrCounterMapCreator->initPlaceNrCounterMap($nrOfPlaces);
    }

    public function count(int $placeNr): int
    {
        return $this->getCounter($placeNr)->count();
    }

    protected function getCounter(int $placeNr): CounterForPlaceNr {
        if( !array_key_exists($placeNr, $this->map) ) {
            throw new \Exception('placeNr not in map');
        }
        return $this->map[$placeNr];
    }

//    /**
//     * @return list<int>
//     */
//    public function getPlaceNrsGreaterThan(int $minCount): array {
//        $placeNrs = [];
//        foreach( $this->map as $counter ) {
//            if( $counter->count() > $minCount) {
//                $placeNrs[] = $counter->getPlaceNr();
//            }
//        }
//        return $placeNrs;
//    }
//
//    /**
//     * @return list<int>
//     */
//    public function getPlaceNrsSmallerThan(int $maxCount): array {
//        $placeNrs = [];
//        foreach( $this->map as $counter ) {
//            if( $counter->count() < $maxCount) {
//                $placeNrs[] = $counter->getPlaceNr();
//            }
//        }
//        return $placeNrs;
//    }

    /**
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @return void
     */
    public function addHomeAways(array $homeAways): void
    {
        foreach ($homeAways as $homeAway) {
            $this->addHomeAway($homeAway);
        }
    }

    public function incrementPlaceNr(int $placeNr): void {

        $this->map[$placeNr] = $this->getCounter($placeNr)->increment();
    }

    public function removeHomeAway(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): void
    {
        foreach ($homeAway->convertToPlaceNrs() as $placeNr) {
            $this->decrementPlaceNr($placeNr);
        }
    }

    public function decrementPlaceNr(int $placeNr): void {
        $this->map[$placeNr] = $this->getCounter($placeNr)->decrement();
    }

    /**
     * @return non-empty-array<int, list<CounterForPlaceNr>>
     */
    public function createCountersPerAmountMap(): array {
        $perAmount = [];
        foreach ($this->map as $placeNrCounter) {
            $count = $placeNrCounter->count();
            if (!array_key_exists($count, $perAmount)) {
                $perAmount[$count] = [];
            }
            $perAmount[$count][] = $placeNrCounter;
        }
        ksort($perAmount);
        return $perAmount;
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

    abstract public function addHomeAway(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): void;
}
