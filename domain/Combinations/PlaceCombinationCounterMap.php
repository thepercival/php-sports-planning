<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations;

use Psr\Log\LoggerInterface;
use SportsHelpers\SportRange;
use SportsPlanning\Combinations\Amount\Range as AmountRange;

final class PlaceCombinationCounterMap
{
    /**
     * @var array<string, PlaceCombinationCounter>
     */
    private array $map;
    private bool|null $canBeBalanced = null;
    private AmountRange|null $range = null;
    /**
     * @var array<int, Amount>|null
     */
    private array|null $amounts = null;
    /**
     * @var array<int, list<PlaceCombinationCounter>>
     */
    private array|null $perAmount = null;

    /**
     * @param array<string, PlaceCombinationCounter> $placeCombinationCounters
     */
    public function __construct(array $placeCombinationCounters)
    {
        $this->map = $placeCombinationCounters;
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

//    /**
//     * @return array<string, PlaceCombinationCounter>
//     */
//    public function getMap(): array
//    {
//        return $this->map;
//    }

    public function getAmountRange(): SportRange|null {
        $range = $this->getRange();
        return $range !== null ? new SportRange($range->getMin()->amount, $range->getMax()->amount) : null;
    }

    public function getRange(): AmountRange|null {
        if( $this->range === null) {
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
            $perAmount = $this->getPerAmount();
            foreach ($perAmount as $amount => $combinationCounters) {
                $this->amounts[$amount] = new Amount($amount, count($combinationCounters));
            }
        }
        return $this->amounts;
    }

    /**
     * @return array<int, list<PlaceCombinationCounter>>
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
        foreach( $this->getPlaceCombinationCounters() as $counterIt ) {
            $line .= ((string)$counterIt->getPlaceCombination()) . ' ' . $counterIt->count() . 'x, ';
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
