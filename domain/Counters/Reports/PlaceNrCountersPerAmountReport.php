<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Reports;

use SportsHelpers\SportRange;
use SportsPlanning\Combinations\AmountCalculator as AmountCalculator;
use SportsPlanning\Counters\CounterForAmount;
use SportsPlanning\Combinations\AmountRange as AmountRange;
use SportsPlanning\Counters\CounterForPlaceNr;
use SportsPlanning\Counters\Maps\Schedule\AmountNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\SideNrCounterMap;

readonly final class PlaceNrCountersPerAmountReport extends CountersPerAmountReportAbstract
{
//    private AmountRange $range;
//    /**
//     * @var non-empty-array<int, int>
//     */
//    private array $amountMap;
//    /**
//     * @var non-empty-array<int, list<CounterForPlaceNr>>
//     */
//    private array $countersPerAmountMap;
//
//    public int $nrOfPlaces;

    public function __construct(AmountNrCounterMap|SideNrCounterMap $placeNrCounterMap)
    {
//        $this->nrOfPlaces = $placeNrCounterMap->getNrOfPlaces();
//        $this->countersPerAmountMap = $placeNrCounterMap->createCountersPerAmountMap();
//
//        $amountMap = [];
//        foreach ($this->countersPerAmountMap as $amount => $counterForPlaceNr) {
//            $amountMap[$amount] = count($counterForPlaceNr);
//        }
//        $this->amountMap = $amountMap;
//
//        $min = array_shift($amountMap);
//        $max = array_pop($amountMap);
//        if( $max === null) {
//            $max = $min;
//        }
//        $this->range = new AmountRange($min, $max);
        parent::__construct($placeNrCounterMap);
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

//    /**
//     * @param int $amount
//     * @return list<int>
//     */
//    public function getPlaceNrsWithSameAmount(int $amount): array {
//        if( !array_key_exists($amount, $this->amountMap) ) {
//            return [];
//        }
//        return array_map( function(CounterForPlaceNr $counter): int {
//            return $counter->getPlaceNr();
//        }, $this->countersPerAmountMap[$amount]);
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
