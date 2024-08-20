<?php

namespace SportsPlanning\Counters\Maps\Schedule;

use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\AmountRange as AmountRange;
use SportsPlanning\Counters\Reports\PlaceNrCountersPerAmountReport;
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

//    /**
//     * @return list<int>
//     */
//    public function getPlaceNrsGreaterThanMaximum(): array {
//        $maxAmount = $this->allowedRange->max->getAmount();
//        return $this->map->getPlaceNrsGreaterThan($maxAmount);
//    }

//    /**
//     * @return list<int>
//     */
//    public function getPlaceNrsSmallerThanMinimum(): array {
//        $minAmount = $this->allowedRange->min->getAmount();
//        return $this->map->getPlaceNrsSmallerThan($minAmount);
//    }

    public function createPerAmountReport(): PlaceNrCountersPerAmountReport
    {
        return new PlaceNrCountersPerAmountReport($this->map);
    }

    public function count(int $placeNr): int
    {
        return $this->map->count($placeNr);
    }

//    public function getNrOfEntitiesForAmount(int $amount): int {
//        return count((new PlaceNrCountersPerAmountReport($this->map))->getPlaceNrsWithSameAmount($amount));
//    }

    public function withinRange(int $nrOfCombinationsToGo): bool
    {
        return $this->minimumCanBeReached($nrOfCombinationsToGo) && !$this->aboveMaximum($nrOfCombinationsToGo);
    }

    public function minimumCanBeReached(int $nrOfPlacesToGo): bool
    {
        $perAmountReport = $this->createPerAmountReport();
        $nrSmallerThan = $perAmountReport->calculateSmallerThan($this->allowedRange->min);
        if( $nrOfPlacesToGo >= $nrSmallerThan ) {
            return true;
        };

//        if ( $perAmountReport->range->min->getAmount() === $this->allowedRange->min->getAmount()
//            && $perAmountReport->range->min->count() + $nrOfPlacesToGo <= $this->allowedRange->min->count()
//        ) {
//            return true;
//        }
        return false;
    }

    public function aboveMaximum(int $nrOfPlacesToGo): bool
    {
        $perAmountReport = $this->createPerAmountReport();
        if( $perAmountReport->calculateGreaterThan($this->allowedRange->max) === 0 ) {
            return false;
        }

        if ( $perAmountReport->range->max->getAmount() === $this->allowedRange->max->getAmount()
            &&
            (
                $perAmountReport->range->max->count() + $nrOfPlacesToGo <= $perAmountReport->nrOfPlaces
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