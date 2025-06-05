<?php

namespace SportsPlanning\Counters\Reports;

use SportsPlanning\Combinations\AmountCalculator as AmountCalculator;
use SportsPlanning\Combinations\AmountRange as AmountRange;
use SportsPlanning\Counters\Maps\PlaceNrCounterMapAbstract;
use SportsPlanning\Counters\Maps\Schedule\AmountNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\SideNrCounterMap;

final readonly class RangedPlaceNrCountersReport
{
    // private int|null $shortage = null;
    // private bool|null $overAssigned = null;
//    public PlaceNrCountersPerAmountReport $report;
//
//    private int $totalAboveMaximum;

    public function __construct(AmountNrCounterMap|SideNrCounterMap $map, public AmountRange $allowedRange)
    {
//        $this->report = new PlaceNrCountersPerAmountReport($map);
//
//
//        $this->totalAboveMaximum = $this->report->calculateGreaterThan($this->allowedRange->max);
    }


//    public function getTotalAboveMaximum(): int
//    {
//        return $this->totalAboveMaximum;
//    }

//    public function count(Place $place): int
//    {
//        return $this->map->count($place);
//    }

//    /**
//     * @return array<int, Amount>
//     */
//    public function getAmountMap(): array {
//        return $this->report->getAmountMap();
//    }

//    public function getNrOfEntitiesWithAmount(int $amount): int {
//        $amountMap = $this->report->getAmountMap();
//        return array_key_exists($amount, $amountMap) ? $amountMap[$amount]->nrOfEntitiesWithSameAmount : 0;
//    }
//
//    public function getAmountDifference(): int
//    {
//        return $this->report->getAmountDifference();
//    }
//
//
//
//    public function getMinAmount(): int
//    {
//        return $this->report->getMinAmount();
//    }
//
//
//    public function getNrOfEntitiesWithMinAmount(): int
//    {
//        return $this->report->getNrOfEntitiesWithMinAmount();
//    }
//
//    public function getMaxAmount(): int
//    {
//        return $this->report->getMaxAmount();
//    }
//
//    public function getNrOfEntitiesWithMaxAmount(): int
//    {
//        return $this->report->getNrOfEntitiesWithMaxAmount();
//    }
//
//    public function withinRange(int $nrOfCombinationsToGo): bool
//    {
//        return $this->minimumCanBeReached($nrOfCombinationsToGo) && !$this->aboveMaximum($nrOfCombinationsToGo);
//    }
//
//    public function minimumCanBeReached(int $nrOfCombinationsToGo): bool
//    {
//        if( $this->getTotalBelowMinimum() <= $nrOfCombinationsToGo ) {
//            return true;
//        };
//
//        if ( $this->getMinAmount() === $this->allowedRange->min->amount
//            && $this->getNrOfEntitiesWithMinAmount() + $nrOfCombinationsToGo <= $this->nrOfPlaces
//        ) {
//            return true;
//        }
//        return false;
//    }
//
//    public function aboveMaximum(int $nrOfCombinationsToGo): bool
//    {
//        if( $this->getTotalAboveMaximum() === 0 ) {
//            return false;
//        }
//
//        if ( $this->getMaxAmount() === $this->allowedRange->max->amount
//            &&
//            (
//            $this->getNrOfEntitiesWithMaxAmount() + $nrOfCombinationsToGo <= $this->nrOfPlaces
//            )
//        ) {
//            return false;
//        }
//        return true;
//    }

    public function output(): string {
        return 'laat hier zien wat de range is, maar dit hoeft nog niet omdat er misschien helemaal niet meer met ranges wordt gewerkt';
    }

}