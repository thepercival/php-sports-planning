<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Reports;

use SportsHelpers\SportRange;
use SportsPlanning\Combinations\Amount;
use SportsPlanning\Combinations\Amount\Range as AmountRange;
use SportsPlanning\Counters\CounterForPlaceCombination;

readonly final class PlaceCombinationCountersReport
{
    private bool $canBeBalanced;
    private AmountRange|null $range;
    /**
     * @var array<int, Amount>
     */
    private array $amounts;
    /**
     * @var array<int, list<CounterForPlaceCombination>>
     */
    private array $perAmount;

    /**
     * @param array<string, CounterForPlaceCombination> $placeCombinationCounters
     */
    final public function __construct(array $placeCombinationCounters)
    {
        $this->perAmount = $this->calculatePerAmount($placeCombinationCounters);
        $this->amounts = $this->calculateAmountMap($this->perAmount);
        $this->range = $this->calculateRange($this->amounts);
        $this->canBeBalanced = $this->calculateCanBeBalanced($placeCombinationCounters);
    }


    /**
     * @param array<string, CounterForPlaceCombination> $placeCombinationCounters
     * @return array<int, list<CounterForPlaceCombination>>
     */
    private function calculatePerAmount(array $placeCombinationCounters): array {
        $perAmount = [];
        foreach ($placeCombinationCounters as $combinationCounter) {
            $count = $combinationCounter->count();
            if (!array_key_exists($count, $perAmount)) {
                $perAmount[$count] = [];
            }
            $perAmount[$count][] = $combinationCounter;
        }
        ksort($perAmount);
        return $perAmount;
    }

    /**
     * @param array<int, list<CounterForPlaceCombination>> $perAmount
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
     * @param array<string, CounterForPlaceCombination> $placeCombinationCounters
     * @return bool
     */
    private function calculateCanBeBalanced(array $placeCombinationCounters): bool {
        $totalCount = 0;
        foreach( $placeCombinationCounters as $placeCombinationCounter) {
            $totalCount += $placeCombinationCounter->count();
        }
        return (($totalCount % count($placeCombinationCounters)) === 0);
    }

    public function getAmountRange(): SportRange|null {
        $range = $this->getRange();
        return $range !== null ? new SportRange($range->getMin()->amount, $range->getMax()->amount) : null;
    }

    public function getRange(): AmountRange|null {
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

    /**
     * @return array<int, Amount>
     */
    public function getAmountMap(): array {
        return $this->amounts;
    }

    /**
     * @return array<int, list<CounterForPlaceCombination>>
     */
    public function getPerAmount(): array {
        return $this->perAmount;
    }
}
