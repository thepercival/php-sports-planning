<?php

namespace SportsPlanning\Counters\Maps;

use SportsPlanning\Combinations\Amount\Calculator as AmountCalculator;
use SportsPlanning\Combinations\Amount\Range as AmountRange;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Counters\Reports\PlaceCombinationCountersReport;
use SportsPlanning\Counters\Reports\RangedPlaceCombinationCountersReport;

readonly class RangedPlaceCombinationCounterMap
{
    // private int|null $shortage = null;
    // private bool|null $overAssigned = null;
    private RangedPlaceCombinationCountersReport $report;
    private PlaceCombinationCounterMap $map;
//    private readonly int $nrOfPlaceCombinationsBelowMinimum;
//    private readonly int $nrOfPlaceCombinationsAboveMaximum;
    private AmountRange $allowedRange;

    public function __construct(PlaceCombinationCounterMap $map, AmountRange $allowedRange) {
        $this->map = $map;
        $this->allowedRange = $allowedRange;
        $this->report = new RangedPlaceCombinationCountersReport($map, $allowedRange);
    }

    public function getAllowedRange(): AmountRange {
        return $this->allowedRange;
    }

//    protected function getMap(): PlaceCombinationCounterMap {
//        return $this->map;
//    }

    public function addPlaceCombination(PlaceCombination $placeCombination): self {

        return new self($this->map->addPlaceCombination($placeCombination), $this->allowedRange );
    }

    public function removePlaceCombination(PlaceCombination $placeCombination): self {

        return new self($this->map->removePlaceCombination($placeCombination), $this->allowedRange);
    }


    public function count(PlaceCombination $placeCombination): int
    {
        return $this->map->count($placeCombination);
    }

    public function countAmount(int $amount): int {
        $amountMap = $this->map->getReport()->getAmountMap();
        return array_key_exists($amount, $amountMap) ? $amountMap[$amount]->count : 0;
    }

    public function getAmountDifference(): int
    {
        return $this->map->getReport()->getAmountDifference();
    }

    public function getRange(): AmountRange|null
    {
        return $this->map->getReport()->getRange();
    }

    public function getMinAmount(): int
    {
        return $this->map->getReport()->getMinAmount();
    }


    public function getCountOfMinAmount(): int
    {
        return $this->map->getReport()->getCountOfMinAmount();
    }

    public function getMaxAmount(): int
    {
        return $this->map->getReport()->getMaxAmount();
    }

    public function getCountOfMaxAmount(): int
    {
        return $this->map->getReport()->getCountOfMaxAmount();
    }

    public function withinRange(int $nrOfCombinationsToGo): bool
    {
        return $this->minimumCanBeReached($nrOfCombinationsToGo) && !$this->report->aboveMaximum($nrOfCombinationsToGo);
    }

    public function minimumCanBeReached(int $nrOfCombinationsToGo): bool
    {
        return $this->report->minimumCanBeReached($nrOfCombinationsToGo);
    }


}