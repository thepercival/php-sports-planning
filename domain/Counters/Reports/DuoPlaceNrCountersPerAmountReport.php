<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Reports;

use SportsHelpers\SportRange;
use SportsPlanning\Combinations\AmountCalculator as AmountCalculator;
use SportsPlanning\Combinations\AmountRange as AmountRange;
use SportsPlanning\Counters\CounterForAmount;
use SportsPlanning\Counters\CounterForDuoPlaceNr;
use SportsPlanning\Counters\Maps\Schedule\AgainstNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\TogetherNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\WithNrCounterMap;

readonly final class DuoPlaceNrCountersPerAmountReport extends CountersPerAmountReportAbstract
{
    public function __construct(AgainstNrCounterMap|WithNrCounterMap|TogetherNrCounterMap $duoPlaceNrCounterMap)
    {
//        $this->nrOfPlaces = $duoPlaceNrCounterMap->getNrOfPlaces();
//        $this->countersPerAmountMap = $duoPlaceNrCounterMap->createCountersPerAmountMap();
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

//        $this->canBeBalanced = $placeNrCounterMap->ca
        parent::__construct($duoPlaceNrCounterMap);
    }

//    private function canBeBalanced(): bool {
//        $totalCount = 0;
//        foreach( $this->countersPerAmountMap as $placeNrCounter) {
//            $totalCount += $placeNrCounter->count();
//        }
//        return (($totalCount % count($this->countersPerAmountMap)) === 0);
//    }

//
//    /**
//     * @param array<string, CounterForDuoPlaceNr> $placeCombinationCounters
//     * @return array<int, list<CounterForDuoPlaceNr>>
//     */
//    private function calculatePerAmount(array $placeCombinationCounters): array {
//        $perAmount = [];
//        foreach ($placeCombinationCounters as $combinationCounter) {
//            $count = $combinationCounter->count();
//            if (!array_key_exists($count, $perAmount)) {
//                $perAmount[$count] = [];
//            }
//            $perAmount[$count][] = $combinationCounter;
//        }
//        ksort($perAmount);
//        return $perAmount;
//    }
//
//    /**
//     * @param array<int, list<CounterForDuoPlaceNr>> $perAmount
//     * @return array<int, Amount>
//     */
//    private function calculateAmountMap(array $perAmount): array {
//
//        $amounts = [];
//        foreach ($perAmount as $amount => $combinationCounters) {
//            $amounts[$amount] = new Amount($amount, count($combinationCounters));
//        }
//        return $amounts;
//    }
//
//    /**
//     * @param array<int, Amount> $amountMap
//     * @return AmountRange|null
//     */
//    private function calculateRange(array $amountMap): AmountRange|null {
//        $min = array_shift($amountMap);
//        $max = array_pop($amountMap);
//        if( $min === null || $max === null) {
//            return null;
//        }
//        return new AmountRange($min, $max);
//    }
//
//    /**
//     * @param array<string, CounterForDuoPlaceNr> $placeCombinationCounters
//     * @return bool
//     */
//    private function calculateCanBeBalanced(array $placeCombinationCounters): bool {
//        $totalCount = 0;
//        foreach( $placeCombinationCounters as $placeCombinationCounter) {
//            $totalCount += $placeCombinationCounter->count();
//        }
//        return (($totalCount % count($placeCombinationCounters)) === 0);
//    }

//
//    public function getAmountDifference(): int {
//        return $this->getAmountRange()?->difference() ?? 0;
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
//        return $this->getMin()?->nrOfEntitiesWithSameAmount ?? 0;
//    }
//
////    public function getMax(): CounterForAmount|null {
////        return $this->range->max;
////    }
//
//    public function getMaxAmount(): int {
//        return $this->range->max->getAmount();
//    }
//
//    public function getNrOfEntitiesWithMaxAmount(): int {
//        return $this->range->max->count();
//    }
//
//    /**
//     * @return array<int, CounterForAmount>
//     */
//    public function getAmountMap(): array {
//        return $this->amounts;
//    }
//
//    /**
//     * @return array<int, list<CounterForDuoPlaceNr>>
//     */
//    public function getPerAmount(): array {
//        return $this->perAmount;
//    }
}
