<?php

namespace SportsPlanning\Counters\Maps\Schedule;

use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\AmountRange as AmountRange;
use SportsPlanning\Counters\Reports\PlaceNrCountersPerAmountReport;
use SportsPlanning\Counters\Reports\RangedPlaceNrCountersReport;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

class RangedPlaceNrCounterMap
{
    public function __construct(
        protected AmountNrCounterMap|SideNrCounterMap $map, protected readonly AmountRange $allowedRange) {
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
        $maxAmount = $this->allowedRange->max->getAmount();
        return $this->map->getPlaceNrsGreaterThan($maxAmount);
    }

    /**
     * @return list<int>
     */
    public function getPlaceNrsSmallerThanMinimum(): array {
        $minAmount = $this->allowedRange->min->getAmount();
        return $this->map->getPlaceNrsSmallerThan($minAmount);
    }

    public function calculateReport(): RangedPlaceNrCountersReport
    {
        return new RangedPlaceNrCountersReport($this->map, $this->allowedRange);
    }

    public function count(int $placeNr): int
    {
        return $this->map->count($placeNr);
    }

    public function getNrOfEntitiesForAmount(int $amount): int {
        return count((new PlaceNrCountersPerAmountReport($this->map))->getPlaceNrsWithSameAmount($amount));
    }

    public function withinRange(int $nrOfCombinationsToGo): bool
    {
        return $this->minimumCanBeReached($nrOfCombinationsToGo) && !$this->aboveMaximum($nrOfCombinationsToGo);
    }

    public function minimumCanBeReached(int $nrOfPlacesToGo): bool
    {
        $report = $this->calculateReport();
        if( $report->getTotalBelowMinimum() <= $nrOfPlacesToGo ) {
            return true;
        };

        $nrOfPlaces = $report->nrOfPlaces;

        if ( $report->getMinAmount() === $this->allowedRange->min->getAmount()
            && $report->getNrOfEntitiesWithMinAmount() + $nrOfPlacesToGo <= $nrOfPlaces
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

        $nrOfPossibleCombinations = $report->getNOfPlaces();

        if ( $report->getMaxAmount() === $this->allowedRange->max->getAmount()
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