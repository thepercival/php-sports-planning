<?php

namespace SportsPlanning\Counters\Maps\Schedule;

use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\AmountRange as AmountRange;
use SportsPlanning\Counters\Maps\PlaceNrCounterMap;
use SportsPlanning\Counters\Reports\RangedPlaceNrCountersReport;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

class RangedPlaceNrCounterMap
{
    public function __construct(
        protected AmountNrCounterMap|SideNrCounterMap|PlaceNrCounterMap $map, protected readonly AmountRange $allowedRange) {
    }

    public function getAllowedRange(): AmountRange {
        return $this->allowedRange;
    }

    public function addHomeAway(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): void
    {
        $this->map->addHomeAway($homeAway);
    }

    public function incrementPlaceNr(int $placeNr): void {

        $this->map->incrementPlaceNr($placeNr);
    }

    public function decrementPlaceNr(int $placeNr): void {

        $this->map->decrementPlaceNr($placeNr);
    }

    /**
     * @return list<int>
     */
    public function getPlaceNrsGreaterThanMaximum(): array {
        $maxAmount = $this->allowedRange->max->amount;
        return $this->map->getPlaceNrsGreaterThan($maxAmount);
    }

    /**
     * @return list<int>
     */
    public function getPlaceNrsSmallerThanMinimum(): array {
        $minAmount = $this->allowedRange->min->amount;
        return $this->map->getPlaceNrsSmallerThan($minAmount);
    }

    public function calculateReport(): RangedPlaceNrCountersReport
    {
        return new RangedPlaceNrCountersReport($this->map, $this->allowedRange);
    }

    public function count(int|null $placeNr = null): int
    {
        return $this->map->count($placeNr);
    }

    public function countAmount(int $amount): int {
        $amountMap = $this->map->calculateReport()->getAmountMap();
        return array_key_exists($amount, $amountMap) ? $amountMap[$amount]->nrOfEntitiesWithSameAmount : 0;
    }

    public function withinRange(int $nrOfCombinationsToGo): bool
    {
        return $this->minimumCanBeReached($nrOfCombinationsToGo) && !$this->aboveMaximum($nrOfCombinationsToGo);
    }

    public function minimumCanBeReached(int $nrOfCombinationsToGo): bool
    {
        $report = $this->calculateReport();
        if( $report->getTotalBelowMinimum() <= $nrOfCombinationsToGo ) {
            return true;
        };

        $nrOfPossibleCombinations = $report->getNOfPossibleCombinations();

        if ( $report->getMinAmount() === $this->allowedRange->min->amount
            && $report->getNrOfEntitiesWithMinAmount() + $nrOfCombinationsToGo <= $nrOfPossibleCombinations
        ) {
            return true;
        }
        return false;
    }

    public function aboveMaximum(int $nrOfCombinationsToGo): bool
    {
        $report = $this->calculateReport();
        if( $report->getTotalAboveMaximum() === 0 ) {
            return false;
        }

        $nrOfPossibleCombinations = $report->getNOfPossibleCombinations();

        if ( $report->getMaxAmount() === $this->allowedRange->max->amount
            &&
            (
                $report->getNrOfEntitiesWithMaxAmount() + $nrOfCombinationsToGo <= $nrOfPossibleCombinations
            )
        ) {
            return false;
        }
        return true;
    }

    public function cloneAsSideNrCounterMap(): SideNrCounterMap {
        if( $this->map instanceof SideNrCounterMap) {
            return clone $this->map;
        }
        throw new \Exception('map must be a SideNrCounterMap');
    }

    public function output(LoggerInterface $logger, string $prefix, string $header): void {


        $this->map->output($logger, $prefix, $header);
    }
}