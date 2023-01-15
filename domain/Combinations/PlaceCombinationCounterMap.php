<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations;

use Psr\Log\LoggerInterface;
use SportsHelpers\SportRange;
use SportsPlanning\Combinations\Amount\Range as AmountRange;

class PlaceCombinationCounterMap
{
    /**
     * @var array<string, PlaceCombinationCounter>
     */
    private array $map;
    private bool|null $canBeBalanced = null;
    private AmountRange|null|false $range = false;
    /**
     * @var array<int, Amount>|null
     */
    private array|null $amounts = null;
    // private int|null $nrOfAssignedToMin = null;
    // private int|null $nrOfAssignedToMax = null;

    /**
     * @param array<string, PlaceCombinationCounter> $placeCombinationCounters
     */
    public function __construct(array $placeCombinationCounters)
    {
        $this->map = $placeCombinationCounters;
//        foreach( $placeCombinationCounters as $placeCombinationCounter) {
//            $this->map[$placeCombinationCounter->getIndex()] = $placeCombinationCounter;
//        }
    }

    public function getPlaceCombination(string $index): PlaceCombination
    {
        return $this->map[$index]->getPlaceCombination();
    }

    public function count(PlaceCombination $placeCombination): int
    {
        if( !array_key_exists($placeCombination->getIndex(), $this->map) ) {
            return 0;
        }
        return $this->map[$placeCombination->getIndex()]->count();
    }

    /**
     * @return list<PlaceCombinationCounter>
     */
    public function getPlaceCombinationCounters(): array
    {
        return array_values($this->map);
    }

    public function addPlaceCombination(PlaceCombination $placeCombination): self {

        $newCounter = $this->map[$placeCombination->getIndex()]->increment2();
        $map = $this->map;
        $map[$placeCombination->getIndex()] = $newCounter;

        return new self($map);
    }

    public function removePlaceCombination(PlaceCombination $placeCombination): self {

        $newCounter = $this->map[$placeCombination->getIndex()]->decrement();
        $map = $this->map;
        $map[$placeCombination->getIndex()] = $newCounter;

        return new self($map);
    }

    /**
     * @return list<PlaceCombinationCounter>
     */
    public function getList(): array
    {
        return array_values($this->map);
    }

//    public function getMinAmount(): int {
//        return $this->getAmountRange()->minimum->amount ?? 0;
//    }

//    public function getNrOfAssignedToMin(): int {
//        $this->getMin();
//        if( $this->nrOfAssignedToMin === null) {
//            throw new \Exception('should always be set');
//        }
//        return $this->nrOfAssignedToMin;
//    }


//    public function getMaxAmount(): int {
//        // return $this->getValueRange()->getMax();
//        return $this->getAmountRange()->maximum->amount ?? 0;
//    }

//    public function getNrOfAssignedToMax(): int {
//        $this->getMax();
//        if( $this->nrOfAssignedToMax === null) {
//            throw new \Exception('should always be set');
//        }
//        return $this->nrOfAssignedToMax;
//    }

//    public function getCountRange(): SportRange|null {
//        $range = $this->getRange();
//        return $range ? new SportRange($range->minimum->count, $range->maximum->count) : null;
//    }

    public function getAmountRange(): SportRange|null {
        $range = $this->getRange();
        return $range ? new SportRange($range->getMinAmount(), $range->getMaxAmount()) : null;
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

//    public function getNrOfAssignedTo(int $amount): int {
//        $amountRange = $this->getAmounts();
//        if( !array_key_exists($amount, $perAmount) ) {
//            return 0;
//        }
//        return count($perAmount[$amount]);
//    }


//    public function getMinDifference(): int {
//        return $this->canBeBalanced() ? 0 : 1;
//    }

    public function getAmountDifference(): int {
        return $this->getAmountRange()?->difference() ?? 0;
    }

    public function getMaxAmount(): int {
        return $this->getAmountRange()?->getMax() ?? 0;
    }


    protected function canBeBalanced(): bool {
        if( $this->canBeBalanced === null) {
            $totalCount = 0;
            foreach( $this->map as $placeCombinationCounter) {
                $totalCount += $placeCombinationCounter->count();
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
            foreach ($this->map as $combinationCounter) {
                if (!array_key_exists($combinationCounter->count(), $this->amounts)) {
                    $this->amounts[$combinationCounter->count()] = new Amount($combinationCounter->count(), 1);
                } else {
                    $amount = $this->amounts[$combinationCounter->count()];
                    $this->amounts[$combinationCounter->count()] = new Amount($amount->amount, $amount->count + 1);
                }
            }
            ksort($this->amounts);
        }
        return $this->amounts;
    }

//    /**
//     * @return array<int, list<PlaceCombinationCounter>>
//     */
//    public function getPerAmountOld(): array {
//        if( $this->perAmount === null) {
//            $this->perAmount = [];
//            foreach ($this->map as $combinationCounter) {
//                if (!array_key_exists($combinationCounter->count(), $this->perAmount)) {
//                    $this->perAmount[$combinationCounter->count()] = [];
//                }
//                $this->perAmount[$combinationCounter->count()][] = $combinationCounter;
//            }
//            ksort($this->perAmount);
//        }
//        return $this->perAmount;
//    }


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
