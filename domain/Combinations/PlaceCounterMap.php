<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations;

use Psr\Log\LoggerInterface;
use SportsHelpers\SportRange;
use SportsPlanning\Place;
use SportsPlanning\PlaceCounter;

class PlaceCounterMap
{
    /**
     * @var array<int, PlaceCounter>
     */
    private array $map;
    private SportRange|null $valueRange = null;

    /**
     * @param list<PlaceCounter> $placeCounters
     */
    public function __construct(array $placeCounters)
    {
        $this->map = [];
        foreach( $placeCounters as $placeCounter) {
            $this->map[$placeCounter->getNumber()] = $placeCounter;
        }
    }

    public function getPlace(int $number): Place
    {
        return $this->map[$number]->getPlace();
    }

    public function count(Place $place): int
    {
        if( !array_key_exists($place->getNumber(), $this->map) ) {
            return 0;
        }
        return $this->map[$place->getNumber()]->count();
    }

    /**
     * @return list<PlaceCounter>
     */
    public function getPlaceCounters(): array
    {
        return array_values($this->map);
    }

    public function addPlace(Place $place): self {

        $newCounter = $this->map[$place->getNumber()]->increment2();
        $map = $this->map;
        $map[$place->getNumber()] = $newCounter;


        return new self(
            array_values($map)
        );
    }

    public function removePlace(Place $place): self {

        $newCounter = $this->map[$place->getNumber()]->decrement();
        $map = $this->map;
        $map[$place->getNumber()] = $newCounter;

        return new self(
            array_values($map)
        );
    }

    /**
     * @return list<PlaceCounter>
     */
    public function getList(): array
    {
        return array_values($this->map);
    }

    public function getMaxDifference(): int {
        return $this->getValueRange()->difference();
    }

    public function getMax(): int {
        return $this->getValueRange()->getMax();
    }

    public function getValueRange(): SportRange {
        if( $this->valueRange === null) {
            $perAmount = $this->getPerAmount();
            $min = 0;
            $max = 0;
            foreach( array_keys($perAmount) as $amount) {
                if( $min === 0 ) {
                    $min = $amount;
                }
                $max = $amount;
            }
            $this->valueRange = new SportRange($min, $max);
        }
        return $this->valueRange;

    }

    /**
     * @return array<int, list<PlaceCounter>>
     */
    public function getPerAmount(): array {
        $perAmount = [];
        foreach( $this->map as $placeCounter) {
            if( !array_key_exists($placeCounter->count(), $perAmount)) {
                $perAmount[$placeCounter->count()] = [];
            }
            $perAmount[$placeCounter->count()][] = $placeCounter;
        }
        ksort($perAmount);
        return $perAmount;
    }

    public function output(LoggerInterface $logger, string $prefix): void {
        // $logger->info($header);

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
