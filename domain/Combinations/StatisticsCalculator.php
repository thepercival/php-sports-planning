<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations;


use SportsHelpers\Against\Side;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsPlanning\Place;

abstract class StatisticsCalculator
{
    // protected bool $useWith;

    public function __construct(
        protected PlaceCombinationCounterMap $assignedHomeMap,
        protected int $nrOfHomeAwaysAssigned
    )
    {

    }



    public function getNrOfHomeAwaysAssigned(): int {
        return $this->nrOfHomeAwaysAssigned;
    }


    abstract public function addHomeAway(HomeAway $homeAway): self;

    abstract public function allAssigned(): bool;





//        $nrOfIncompletePlaces = 0;
//        foreach ($this->assignedSportMap as $assignedCounter) {
//            if ($assignedCounter->count() < $this->againstWithPoule->getSportVariant()->getNrOfGamesPerPlace()) {
//                $nrOfIncompletePlaces++;
//            }
//
//            if ($nrOfIncompletePlaces >= $this->againstWithPoule->getNrOfGamePlaces()) {
//                return false;
//            }
//        }


//    /**
//     * @param array<int, PlaceCounter> $map
//     * @return array<int, PlaceCounter>
//     */
//    protected function copyPlaceCounterMap(array $map): array {
//        $newMap = [];
//        foreach( $map as $idx => $counter ) {
//            $newMap[$idx] = new PlaceCounter($counter->getPlace(), $counter->count());
//        }
//        return $newMap;
//    }

//    /**
//     * @param array<string, PlaceCombinationCounter> $map
//     * @return array<string, PlaceCombination>
//     */
//    protected function convertToPlaceCombinationMap(array $map): array {
//        $newMap = [];
//        foreach( $map as $idx => $counter ) {
//            $newMap[$idx] = $counter->getPlaceCombination();
//        }
//        return $newMap;
//    }

//    private function getHomeAwayAssigned(AgainstHomeAway $ha): int {
//        $assigned = 0;
//        foreach( $ha->getPlaces() as $place) {
//            if( !array_key_exists($place->getNumber(), $this->assignedSportMap)) {
//               continue;
//            }
//            $assigned += $this->assignedSportMap[$place->getNumber()]->count();
//            if( !array_key_exists($place->getNumber(), $this->assignedMap)) {
//                continue;
//            }
//            $assigned += $this->assignedMap[$place->getNumber()]->count();
//        }
//        return $assigned;
//    }



//    protected function getMaxWithAmount(int $nrOfPlaces, AgainstGpp $sportVariant): int {
//        $maxNrOfSidePlaces = max( $sportVariant->getNrOfHomePlaces(), $sportVariant->getNrOfAwayPlaces() );
//        return (int)ceil($sportVariant->getNrOfGamesPerPlace() / ( ($nrOfPlaces - 1) * ($maxNrOfSidePlaces - 1) ));
//    }
//
//    protected function getMinWithAmount(int $nrOfPlaces, AgainstGpp $sportVariant): int {
//        $minNrOfSidePlaces = min( $sportVariant->getNrOfHomePlaces(), $sportVariant->getNrOfAwayPlaces() );
//        if( $minNrOfSidePlaces === 1) {
//            return 0;
//        }
//        return (int)floor($sportVariant->getNrOfGamesPerPlace() / ( ($nrOfPlaces - 1) * ($minNrOfSidePlaces - 1) ));
//    }

//    protected function getMaxAmountOverMaxAgainstAmountForAllPlaces(): int {
//        $maxAgainst = $this->againstWithPoule->getMaxNrOfAgainstPlacesForPlace() + $this->allowedMargin;
//        $rest = $maxAgainst % ($this->againstWithPoule->getNrOfPlaces() - 1);
//        return ($rest * $this->againstWithPoule->getNrOfPlaces() );
//    }




    // ALL BENEATH SHOULD PERFORM BETTER
//    private function getAgainstAmountAssigned(HomeAway $homeAway): int {
//        $amount = 0;
//        foreach($homeAway->getAgainstPlaceCombinations() as $againstPlaceCombination ) {
//            $amount += $this->assignedAgainstSportMap->count($againstPlaceCombination);
//            $amount += $this->assignedAgainstPreviousSportsMap->count($againstPlaceCombination);
//        }
//        return $amount;
//    }



    /**
     * @param PlaceCounterMap $map
     * @param HomeAway $homeAway
     * @return list<int>
     */
    protected function getLeastAssigned(PlaceCounterMap $map, HomeAway $homeAway): array
    {
        $leastAmount = -1;
        $nrOfPlaces = 0;
        foreach ($homeAway->getPlaces() as $place) {
            $amountAssigned = $map->count($place);
            if ($leastAmount === -1 || $amountAssigned < $leastAmount) {
                $leastAmount = $amountAssigned;
                $nrOfPlaces = 0;
            }
            if ($amountAssigned === $leastAmount) {
                $nrOfPlaces++;
            }
        }
        return [$leastAmount, $nrOfPlaces];
    }

    /**
     * @param PlaceCombinationCounterMap $map
     * @param HomeAway $homeAway
     * @return list<int>
     */
    protected function getLeastCombinationAssigned(PlaceCombinationCounterMap $map, HomeAway $homeAway): array
    {
        $leastAmount = -1;
        $nrOfSides = 0;
        foreach ([Side::Home,Side::Away] as $side ) {
            $sidePlaceCombination = $homeAway->get($side);
            $amountAssigned = $map->count($sidePlaceCombination);
            if ($leastAmount === -1 || $amountAssigned < $leastAmount) {
                $leastAmount = $amountAssigned;
                $nrOfSides = 0;
            }
            if ($amountAssigned === $leastAmount) {
                $nrOfSides++;
            }
        }
        return [$leastAmount, $nrOfSides];
    }

//    protected function getWithAmountAssigned(HomeAway $homeAway): int
//    {
//        $awayWithAmountAssigned = $this->assignedWithSportMap[$homeAway->getAway()->getIndex()]->count();
//        return $this->getHomeWithAmountAssigned($homeAway) + $awayWithAmountAssigned;
//    }
//
//    protected function getHomeWithAmountAssigned(HomeAway $homeAway): int
//    {
//        if( $this->againstWithPoule->getSportVariant()->getNrOfHomePlaces() > 1 ) {
//            return $this->assignedWithSportMap[$homeAway->getHome()->getIndex()]->count();
//        }
//        return 0;
//    }

//    /**
//     * @param AgainstHomeAway $homeAway
//     * @param array<int, AgainstCounter> $assignedAgainstMap
//     * @return int
//     */
//    protected function getAgainstAmountAssigned(AgainstHomeAway $homeAway, array $assignedAgainstMap): int
//    {
//        $home = $homeAway->getHome();
//        $away = $homeAway->getAway();
//        $homeAgainstAmountAssigned = $assignedAgainstMap[$home->getNumber()]->count($away);
//        $awayAgainstAmountAssigned = $assignedAgainstMap[$away->getNumber()]->count($home);
//        return $homeAgainstAmountAssigned + $awayAgainstAmountAssigned;
//    }

}
