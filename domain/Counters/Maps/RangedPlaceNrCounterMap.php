<?php

namespace SportsPlanning\Counters\Maps;

use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\AmountRange as AmountRange;
use SportsPlanning\Counters\Maps\Schedule\AmountNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\SideNrCounterMap;
use SportsPlanning\Counters\Reports\PlaceNrCountersPerAmountReport;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

final class RangedPlaceNrCounterMap extends RangedNrCounterMapAbstract
{
    public function __construct(
        protected AmountNrCounterMap|SideNrCounterMap $map, AmountRange $allowedRange) {
        parent::__construct($allowedRange);
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

    #[\Override]
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