<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations;

use Psr\Log\LoggerInterface;
use SportsHelpers\SportRange;
use SportsPlanning\Combinations\Amount\Range as AmountRange;
use SportsPlanning\Place;
use SportsPlanning\PlaceCounter;

class PlaceCounterMap
{
    /**
     * @var array<int, PlaceCounter>
     */
    private array $map;
    private bool|null $canBeBalanced = null;
    private AmountRange|null|false $range = false;
    /**
     * @var array<int, Amount>|null
     */
    private array|null $amounts = null;
    /**
     * @var array<int, list<PlaceCounter>>
     */
    private array|null $perAmount = null;

    /**
     * @param array<int, PlaceCounter> $placeCounters
     */
    public function __construct(array $placeCounters)
    {
        $this->map = $placeCounters;
    }

    public function getPlace(int $number): Place
    {
        return $this->map[$number]->getPlace();
    }

    public function count(Place|null $place = null): int
    {
        if( $place === null ) {
            return count($this->map);
        }
        if( !array_key_exists($place->getNumber(), $this->map) ) {
            return 0;
        }
        return $this->map[$place->getNumber()]->count();
    }

    /**
     * @return list<PlaceCounter>
     */
    protected function getPlaceCounters(): array
    {
        return array_values($this->map);
    }

    public function addPlace(Place $place): self {

        $newCounter = $this->map[$place->getNumber()]->increment2();
        $map = $this->map;
        $map[$place->getNumber()] = $newCounter;
        return new self($map);
    }

    public function removePlace(Place $place): self {

        $newCounter = $this->map[$place->getNumber()]->decrement();
        $map = $this->map;
        $map[$place->getNumber()] = $newCounter;

        return new self($map);
    }

    public function getAmountDifference(): int {
        return $this->getAmountRange()?->difference() ?? 0;
    }

    public function getMin(): Amount|null {
        return $this->getRange()?->getMin();
    }

    public function getMinAmount(): int {
        return $this->getMin()?->amount ?? 0;
    }

    public function getCountOfMinAmount(): int {
        return $this->getMin()?->count ?? 0;
    }

    public function getMax(): Amount|null {
        return $this->getRange()?->getMax();
    }

    public function getMaxAmount(): int {
        return $this->getMax()?->amount ?? 0;
    }

    public function getCountOfMaxAmount(): int {
        return $this->getMax()?->count ?? 0;
    }

    public function getAmountRange(): SportRange|null {
        $range = $this->getRange();
        return $range ? new SportRange($range->getMin()->amount, $range->getMax()->amount) : null;
    }

    public function getRange(): AmountRange|null {
        if( $this->range === false) {
            $amounts = $this->getAmountMap();
            $min = array_shift($amounts);
            $max = array_pop($amounts);
            if( $min === null || $max === null) {
                $this->range = null;
            } else {
                $this->range = new AmountRange($min, $max);
            }
        }
        return $this->range;
    }

    protected function canBeBalanced(): bool {
        if( $this->canBeBalanced === null) {
            $totalCount = 0;
            foreach( $this->map as $placeCounter) {
                $totalCount += $placeCounter->count();
            }
            $this->canBeBalanced = ($totalCount % count($this->map)) === 0;
        }
        return $this->canBeBalanced;

    }

    /**
     * @return array<int, Amount>
     */
    public function getAmountMap(): array {
        if( $this->amounts === null) {
            $this->amounts = [];
            $perAmount = $this->getPerAmount();
            foreach ($perAmount as $amount => $combinationCounters) {
                $this->amounts[$amount] = new Amount($amount, count($combinationCounters));
            }
        }
        return $this->amounts;
    }

    /**
     * @return array<int, list<PlaceCounter>>
     */
    public function getPerAmount(): array {
        if( $this->perAmount === null) {
            $this->perAmount = [];
            foreach ($this->map as $combinationCounter) {
                $count = $combinationCounter->count();
                if (!array_key_exists($count, $this->perAmount)) {
                    $this->perAmount[$count] = [];
                }
                $this->perAmount[$count][] = $combinationCounter;
            }
            ksort($this->perAmount);
        }
        return $this->perAmount;
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
