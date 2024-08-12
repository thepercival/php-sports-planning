<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Reports;

use SportsHelpers\SportRange;
use SportsPlanning\Combinations\Amount;
use SportsPlanning\Combinations\Amount\Range as AmountRange;
use SportsPlanning\Counters\CounterForPlaceNr;

readonly final class PlaceNrCountersReport
{
    private bool $canBeBalanced;
    private AmountRange|null $range;
    /**
     * @var array<int, Amount>
     */
    private array $amounts;
    /**
     * @var array<int, list<CounterForPlaceNr>>
     */
    private array $perAmount;

    /**
     * @param array<int, CounterForPlaceNr> $placeNrCounters
     */
    public function __construct(array $placeNrCounters)
    {
        $this->perAmount = $this->calculatePerAmount($placeNrCounters);
        $this->amounts = $this->calculateAmountMap($this->perAmount);
        $this->range = $this->calculateRange($this->amounts);
        $this->canBeBalanced = $this->calculateCanBeBalanced($placeNrCounters);
    }

    /**
     * @param array<int, CounterForPlaceNr> $placeNrCounters
     * @return array<int, list<CounterForPlaceNr>>
     */
    private function calculatePerAmount(array $placeNrCounters): array {
        $perAmount = [];
        foreach ($placeNrCounters as $placeNrCounter) {
            $count = $placeNrCounter->count();
            if (!array_key_exists($count, $perAmount)) {
                $perAmount[$count] = [];
            }
            $perAmount[$count][] = $placeNrCounter;
        }
        ksort($perAmount);
        return $perAmount;
    }

    /**
     * @param array<int, list<CounterForPlaceNr>> $perAmount
     * @return array<int, Amount>
     */
    private function calculateAmountMap(array $perAmount): array {

        $amounts = [];
        foreach ($perAmount as $amount => $combinationCounters) {
            $amounts[$amount] = new Amount($amount, count($combinationCounters));
        }
        return $amounts;
    }

    /**
     * @param array<int, Amount> $amountMap
     * @return AmountRange|null
     */
    private function calculateRange(array $amountMap): AmountRange|null {
        $min = array_shift($amountMap);
        $max = array_pop($amountMap);
        if( $min === null || $max === null) {
            return null;
        }
        return new AmountRange($min, $max);
    }

    /**
     * @param array<int, CounterForPlaceNr> $placeNrCounters
     * @return bool
     */
    private function calculateCanBeBalanced(array $placeNrCounters): bool {
        $totalCount = 0;
        foreach( $placeNrCounters as $placeNrCounter) {
            $totalCount += $placeNrCounter->count();
        }
        return (($totalCount % count($placeNrCounters)) === 0);
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


    /**
     * @param int $amount
     * @return list<int>
     */
    public function getPlaceNrsWithSameAmount(int $amount): array {
        $amountMap = $this->getPerAmount();
        if( !array_key_exists($amount, $amountMap) ) {
            return [];
        }
        return array_map( function(CounterForPlaceNr $counter): int {
            return $counter->getPlaceNr();
        }, $amountMap[$amount]);
    }

    public function getAmountRange(): SportRange|null {
        $range = $this->getRange();
        return $range ? new SportRange($range->getMin()->amount, $range->getMax()->amount) : null;
    }

    public function getRange(): AmountRange|null {
        return $this->range;
    }

    protected function canBeBalanced(): bool {
        return $this->canBeBalanced;
    }

    /**
     * @return array<int, Amount>
     */
    public function getAmountMap(): array {
        return $this->amounts;
    }

    /**
     * @return array<int, list<CounterForPlaceNr>>
     */
    public function getPerAmount(): array {
        return $this->perAmount;
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
