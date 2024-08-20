<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Reports;

use SportsHelpers\SportRange;
use SportsPlanning\Combinations\AmountCalculator as AmountCalculator;
use SportsPlanning\Counters\CounterForAmount;
use SportsPlanning\Combinations\AmountRange;
use SportsPlanning\Counters\Maps\DuoPlaceNrCounterMapAbstract;
use SportsPlanning\Counters\Maps\PlaceNrCounterMapAbstract;

readonly abstract class CountersPerAmountReportAbstract
{
    public int $nrOfPlaces;
    public AmountRange $range;
//    /**
//     * @var non-empty-array<int, list<CounterForDuoPlaceNr>>
//     */
//    private array $countersPerAmountMap;
    /**
     * @var non-empty-array<int, CounterForAmount>
     */
    private array $amountCounterMap;

    public function __construct(PlaceNrCounterMapAbstract|DuoPlaceNrCounterMapAbstract $nrCounterMap)
    {
        $this->nrOfPlaces = $nrCounterMap->nrOfPlaces;
        $countersPerAmountMap = $nrCounterMap->createCountersPerAmountMap();

        $amountCounterMap = [];
        foreach ($countersPerAmountMap as $amount => $counterForPlaceNr) {
            $amountCounterMap[$amount] = new CounterForAmount($amount, count($counterForPlaceNr) );
        }
        $this->amountCounterMap = $amountCounterMap;

        $min = array_shift($amountCounterMap);
        $max = array_pop($amountCounterMap);
        if( $max === null) {
            $max = $min;
        }
        $this->range = new AmountRange($min, $max);

//        $this->canBeBalanced = $placeNrCounterMap->ca
    }

//    private function canBeBalanced(): bool {
//        $totalCount = 0;
//        foreach( $this->countersPerAmountMap as $placeNrCounter) {
//            $totalCount += $placeNrCounter->count();
//        }
//        return (($totalCount % count($this->countersPerAmountMap)) === 0);
//    }

//    public function getAmountDifference(): int {
//        return $this->createAmountRange()->difference();
//    }
//
//    public function getMin(): CounterForAmount {
//        return $this->range->min;
//    }
//
//    public function getMinAmount(): int {
//        return $this->range->min->amount ?? 0;
//    }
//
//    public function getNrOfEntitiesWithMinAmount(): int {
//        return $this->range->min->count();
//    }
//
//    public function getMax(): CounterForAmount {
//        return $this->range->max;
//    }
//
//    public function getMaxAmount(): int {
//        return $this->range->max->getAmount();
//    }
//
//    public function getNrOfEntitiesWithMaxAmount(): int {
//        return $this->range->max->count();
//    }

   private function createAmountRange(): SportRange {
        return new SportRange($this->range->min->getAmount(), $this->range->max->getAmount());
    }

    public function calculateSmallerThan(CounterForAmount $minimum): int {
        $calculator = new AmountCalculator();
        return $calculator->calculateSmallerThan($minimum, array_values($this->amountCounterMap));
    }

    public function calculateGreaterThan(CounterForAmount $maximum): int {
        $calculator = new AmountCalculator();
        return $calculator->calculateGreaterThan($maximum, array_values($this->amountCounterMap));
    }

//    /**
//     * @return list<CounterForAmount>
//     */
//    private function convertAmountMapToCounterList(): array {
//        return $this->amountCounterMap;
//    }

//    /**
//     * @return array<int, list<CounterForPlaceNr>>
//     */
//    public function getCountersPerAmount(): array {
//        return $this->perAmount;
//    }

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
