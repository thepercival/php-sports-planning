<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Maps;

use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\CounterForDuoPlaceNr;
use SportsPlanning\Counters\CounterForPlaceNr;
use SportsPlanning\Counters\Reports\DuoPlaceNrCountersPerAmountReport;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

abstract class DuoPlaceNrCounterMapAbstract
{
    /**
     * @var non-empty-array<string, CounterForDuoPlaceNr>
     */
    private array $map;

    public function __construct(public int $nrOfPlaces)
    {
        $duoPlaceNrCounterMapCreator = new DuoPlaceNrCounterMapCreator();
        $this->map = $duoPlaceNrCounterMapCreator->initDuoPlaceNrCounterMap($nrOfPlaces);
    }

//    public function getDuoPlaceNr(string $index): DuoPlaceNr
//    {
//        return $this->map[$index]->getDuoPlaceNr();
//    }

    public function count(DuoPlaceNr $duoPlaceNr): int
    {
        return $this->getCounter($duoPlaceNr)->count();
    }

    protected function getCounter(DuoPlaceNr $duoPlaceNr): CounterForDuoPlaceNr {
        if( !array_key_exists($duoPlaceNr->getIndex(), $this->map) ) {
            throw new \Exception('duoPlaceNr not in map');
        }
        return $this->map[$duoPlaceNr->getIndex()];
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
        $this->map[$duoPlaceNr->getIndex()] = $this->getCounter($duoPlaceNr)->increment();
    }

    public function decrementDuoPlaceNr(DuoPlaceNr $duoPlaceNr): void {
        $this->map[$duoPlaceNr->getIndex()] = $this->getCounter($duoPlaceNr)->decrement();
    }

    /**
     * @return non-empty-array<int, list<CounterForDuoPlaceNr>>
     */
    public function createCountersPerAmountMap(): array {
        $perAmount = [];
        foreach ($this->map as $duoPlaceNrCounter) {
            $count = $duoPlaceNrCounter->count();
            if (!array_key_exists($count, $perAmount)) {
                $perAmount[$count] = [];
            }
            $perAmount[$count][] = $duoPlaceNrCounter;
        }
        ksort($perAmount);
        return $perAmount;
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

    /**
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @return void
     */
    public function addHomeAways(array $homeAways): void
    {
        foreach( $homeAways as $homeAway ) {
            $this->addHomeAway($homeAway);
        }
    }

    abstract public function addHomeAway(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): void;
}
